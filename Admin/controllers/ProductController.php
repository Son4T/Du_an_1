<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/ProductModel.php';

require_admin('../views/login.php');

// Controller này chỉ nhận dữ liệu từ form bằng phương thức POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

/**
 * Lưu ảnh sản phẩm mới và trả về đường dẫn ảnh.
 * Nếu người dùng không chọn ảnh mới thì giữ lại ảnh cũ.
 */
function upload_product_image(string $oldImage = ''): string
{
    $hasNoNewImage = empty($_FILES['product_image'])
        || $_FILES['product_image']['error'] === UPLOAD_ERR_NO_FILE;

    if ($hasNoNewImage) {
        return $oldImage;
    }

    $imageFile = $_FILES['product_image'];

    if ($imageFile['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload ảnh thất bại.');
    }

    if ($imageFile['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Ảnh tối đa 5MB.');
    }

    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($imageFile['tmp_name']);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
    }

    $uploadFolder = dirname(__DIR__) . '/public/uploads/products';

    if (!is_dir($uploadFolder)) {
        mkdir($uploadFolder, 0755, true);
    }

    $fileName = bin2hex(random_bytes(12)) . '.' . $allowedTypes[$mimeType];
    $savedPath = $uploadFolder . '/' . $fileName;

    if (!move_uploaded_file($imageFile['tmp_name'], $savedPath)) {
        throw new RuntimeException('Không thể lưu ảnh.');
    }

    return 'public/uploads/products/' . $fileName;
}

$productModel = new ProductModel($conn);
$action = $_POST['action'] ?? '';

try {
    // Luồng thêm hoặc cập nhật sản phẩm.
    if ($action === 'save') {
        // Bước 1: Lấy thông tin sản phẩm từ form.
        $productId = (int) ($_POST['id'] ?? 0);
        $productName = trim($_POST['product_name'] ?? '');
        $productCode = strtoupper(trim($_POST['product_code'] ?? ''));
        $basePrice = (float) ($_POST['base_price'] ?? 0);
        $salePrice = (float) ($_POST['sale_price'] ?? 0);

        // Bước 2: Kiểm tra dữ liệu cơ bản.
        $invalidBasicInformation = mb_strlen($productName) < 3
            || !preg_match('/^[A-Z0-9_-]{2,30}$/', $productCode)
            || $basePrice <= 0;

        if ($invalidBasicInformation) {
            throw new RuntimeException(
                'Tên, mã hoặc giá sản phẩm chưa hợp lệ. '
                . 'Mã chỉ gồm chữ, số, dấu gạch ngang/gạch dưới.'
            );
        }

        if ($salePrice < 0 || ($salePrice > 0 && $salePrice >= $basePrice)) {
            throw new RuntimeException('Giá khuyến mãi phải nhỏ hơn giá gốc.');
        }

        $productData = [
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'code' => $productCode,
            'name' => $productName,
            'slug' => slugify($_POST['slug'] ?? $productName),
            'description' => trim($_POST['description'] ?? ''),
            'base_price' => $basePrice,
            'sale_price' => $salePrice,
            'image_url' => upload_product_image($_POST['existing_image_url'] ?? ''),
            'status' => in_array($_POST['status'] ?? 'Hiện', ['Hiện', 'Ẩn'], true)
                ? $_POST['status']
                : 'Hiện',
            'featured' => isset($_POST['featured']) ? 1 : 0,
        ];

        if ($productData['category_id'] <= 0) {
            throw new RuntimeException('Vui lòng chọn danh mục.');
        }

        // Bước 3: Tạo danh sách biến thể màu - kích thước.
        $variants = [];
        $colorIds = $_POST['variant_color_id'] ?? [];
        $sizeIds = $_POST['variant_size_id'] ?? [];
        $usedColorSizePairs = [];
        $usedSkus = [];

        foreach ($colorIds as $index => $colorId) {
            $colorId = (int) $colorId;
            $sizeId = (int) ($sizeIds[$index] ?? 0);

            // Dòng biến thể chưa chọn đủ màu và size thì bỏ qua.
            if ($colorId <= 0 || $sizeId <= 0) {
                continue;
            }

            $variantPrice = (float) ($_POST['variant_price'][$index] ?? $basePrice);
            $variantStock = max(0, (int) ($_POST['variant_stock'][$index] ?? 0));
            $variantSku = strtoupper(trim($_POST['variant_sku'][$index] ?? ''));

            if ($variantSku === '') {
                $variantSku = $productCode . '-' . $colorId . '-' . $sizeId;
            }

            $colorSizeKey = $colorId . '-' . $sizeId;

            if (isset($usedColorSizePairs[$colorSizeKey])) {
                throw new RuntimeException(
                    'Mỗi cặp màu và kích thước chỉ được xuất hiện một lần.'
                );
            }

            if (isset($usedSkus[$variantSku])) {
                throw new RuntimeException('SKU biến thể không được trùng nhau.');
            }

            if ($variantPrice <= 0) {
                throw new RuntimeException('Giá của biến thể phải lớn hơn 0.');
            }

            $usedColorSizePairs[$colorSizeKey] = true;
            $usedSkus[$variantSku] = true;

            $variants[] = [
                'color_id' => $colorId,
                'size_id' => $sizeId,
                'sku' => $variantSku,
                'price' => $variantPrice,
                'stock' => $variantStock,
            ];
        }

        if (empty($variants)) {
            throw new RuntimeException(
                'Sản phẩm phải có ít nhất một biến thể màu và kích thước.'
            );
        }

        // Bước 4: Có id thì cập nhật, chưa có id thì thêm mới.
        if ($productId > 0) {
            $saved = $productModel->updateProduct($productId, $productData, $variants);
        } else {
            $saved = $productModel->createProduct($productData, $variants);
        }

        if (!$saved) {
            throw new RuntimeException(
                'Không thể lưu sản phẩm. Kiểm tra mã sản phẩm/SKU có bị trùng không.'
            );
        }

        $message = $productId > 0
            ? 'Cập nhật sản phẩm thành công.'
            : 'Thêm sản phẩm thành công.';

        flash('admin', $message);
        redirect('../views/product_management.php');
    }

    // Luồng ẩn hoặc hiện sản phẩm.
    if ($action === 'toggle') {
        $productId = (int) ($_POST['id'] ?? 0);

        if (!$productModel->toggleStatus($productId)) {
            throw new RuntimeException('Không thể đổi trạng thái sản phẩm.');
        }

        flash('admin', 'Đã cập nhật trạng thái sản phẩm.');
        redirect('../views/product_management.php');
    }

    // Luồng xóa sản phẩm.
    if ($action === 'delete') {
        $productId = (int) ($_POST['id'] ?? 0);

        if ($productId <= 0) {
            throw new RuntimeException('Mã sản phẩm không hợp lệ.');
        }

        if (!$productModel->deleteProduct($productId)) {
            throw new RuntimeException(
                'Không thể xóa sản phẩm. Vui lòng kiểm tra lại dữ liệu liên quan.'
            );
        }

        flash('admin', 'Xóa sản phẩm thành công.');
        redirect('../views/product_management.php');
    }

    throw new RuntimeException('Hành động không hợp lệ.');
} catch (Throwable $error) {
    flash('admin', $error->getMessage(), 'error');

    $productId = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $editUrl = 'product_form.php';

        if ($productId > 0) {
            $editUrl .= '?id=' . $productId;
        }

        redirect('../views/' . $editUrl);
    }

    redirect('../views/product_management.php');
}
