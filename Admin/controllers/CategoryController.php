<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/CategoryModel.php';

require_admin('../views/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$categoryModel = new CategoryModel($conn);
$action = $_POST['action'] ?? '';

try {
    if ($action === 'save') {
        // Bước 1: Lấy dữ liệu từ form.
        $categoryId = (int) ($_POST['id'] ?? 0);
        $categoryName = trim($_POST['name'] ?? '');

        // Bước 2: Kiểm tra dữ liệu.
        if (mb_strlen($categoryName) < 2) {
            throw new RuntimeException('Tên danh mục phải có ít nhất 2 ký tự.');
        }

        $categoryData = [
            'name' => $categoryName,
            'slug' => slugify($_POST['slug'] ?? $categoryName),
            'description' => trim($_POST['description'] ?? ''),
            'status' => in_array(
                $_POST['status'] ?? 'active',
                ['active', 'inactive'],
                true
            ) ? $_POST['status'] : 'active',
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
        ];

        // Bước 3: Có id thì sửa, chưa có id thì thêm.
        if ($categoryId > 0) {
            $saved = $categoryModel->update($categoryId, $categoryData);
        } else {
            $saved = $categoryModel->create($categoryData);
        }

        if (!$saved) {
            throw new RuntimeException('Không thể lưu danh mục.');
        }

        $message = $categoryId > 0
            ? 'Cập nhật danh mục thành công.'
            : 'Thêm danh mục thành công.';

        flash('admin', $message);
    } elseif ($action === 'delete') {
        $categoryId = (int) ($_POST['id'] ?? 0);

        if (!$categoryModel->delete($categoryId)) {
            throw new RuntimeException('Không thể xóa danh mục đang có sản phẩm.');
        }

        flash('admin', 'Xóa danh mục thành công.');
    } else {
        throw new RuntimeException('Hành động không hợp lệ.');
    }
} catch (Throwable $error) {
    flash('admin', $error->getMessage(), 'error');
}

redirect('../views/category_management.php');
