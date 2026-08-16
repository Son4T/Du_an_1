<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/UserModel.php';
require_once dirname(__DIR__) . '/Admin/models/OrderModel.php';
require_once dirname(__DIR__) . '/Admin/services/ZaloPayPaymentService.php';

require_client('login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    flash('client', 'Giỏ hàng đang trống.', 'error');
    redirect('cart.php');
}

// Bước 1: Kiểm tra lại tài khoản đang đặt hàng.
$userId = (int) $_SESSION['client_user_id'];
$userModel = new UserModel($conn);
$user = $userModel->find($userId);

$userIsInvalid = !$user
    || $user['status'] !== 'active'
    || $user['role'] !== 'user';

if ($userIsInvalid) {
    unset(
        $_SESSION['client_user_id'],
        $_SESSION['client_username'],
        $_SESSION['client_name']
    );

    flash(
        'auth',
        'Tài khoản không còn khả dụng. Vui lòng đăng nhập lại.',
        'error'
    );
    redirect('login.php');
}

// Bước 2: Lấy thông tin nhận hàng từ form checkout.
$customerName = trim($_POST['customer_name'] ?? '');
$phone = clean_phone($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$province = trim($_POST['province'] ?? '');
$district = trim($_POST['district'] ?? '');
$ward = trim($_POST['ward'] ?? '');
$addressDetail = trim($_POST['address_detail'] ?? '');
$note = trim($_POST['note'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'cod';

try {
    // Bước 3: Kiểm tra dữ liệu người nhận.
    if (mb_strlen($customerName) < 2 || mb_strlen($customerName) > 120) {
        throw new RuntimeException(
            'Họ tên người nhận phải từ 2 đến 120 ký tự.'
        );
    }

    if (!preg_match('/^0\d{9}$/', $phone)) {
        throw new RuntimeException(
            'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.'
        );
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
        throw new RuntimeException('Email người nhận chưa hợp lệ.');
    }

    $missingAddress = $province === ''
        || $district === ''
        || $ward === ''
        || $addressDetail === '';

    if ($missingAddress) {
        throw new RuntimeException('Vui lòng nhập đầy đủ địa chỉ nhận hàng.');
    }

    if (!in_array($paymentMethod, ['cod', 'zalopay'], true)) {
        throw new RuntimeException('Phương thức thanh toán không hợp lệ.');
    }

    // Database cũ của bản MoMo cần được nâng cấp trước khi lưu ZaloPay.
    if ($paymentMethod === 'zalopay') {
        assert_zalopay_database_ready($conn);
    }

    if (mb_strlen($note) > 1000) {
        throw new RuntimeException('Ghi chú không được vượt quá 1000 ký tự.');
    }

    $fullAddress = "$addressDetail, $ward, $district, $province";

    if (mb_strlen($fullAddress) > 500) {
        throw new RuntimeException('Địa chỉ nhận hàng quá dài.');
    }

    /*
     * Bước 4: Bắt đầu transaction.
     * Từ đây trở đi, nếu một thao tác lỗi thì toàn bộ đơn hàng sẽ rollback.
     */
    $conn->begin_transaction();

    $verifiedItems = [];
    $subtotal = 0;

    // FOR UPDATE khóa tạm dòng tồn kho trong lúc tạo đơn.
    $selectVariant = $conn->prepare(
        "SELECT
            pv.*,
            p.name AS product_name,
            p.status AS product_status,
            p.sale_price,
            c.color_name,
            s.size_name,
            cat.status AS category_status
        FROM product_variants pv
        JOIN products p ON p.id = pv.product_id
        JOIN categories cat ON cat.id = p.category_id
        JOIN colors c ON c.id = pv.color_id
        JOIN sizes s ON s.id = pv.size_id
        WHERE pv.id = ? AND p.id = ?
        FOR UPDATE"
    );

    // Bước 5: Kiểm tra lại từng sản phẩm trong giỏ bằng dữ liệu mới nhất.
    foreach ($cart as $cartItem) {
        $variantId = (int) ($cartItem['variant_id'] ?? 0);
        $productId = (int) ($cartItem['product_id'] ?? 0);
        $quantity = (int) ($cartItem['quantity'] ?? 0);

        $selectVariant->bind_param('ii', $variantId, $productId);
        $selectVariant->execute();
        $variant = $selectVariant->get_result()->fetch_assoc();

        $variantIsInvalid = !$variant
            || $variant['product_status'] !== 'Hiện'
            || $variant['category_status'] !== 'active'
            || $quantity < 1;

        if ($variantIsInvalid) {
            throw new RuntimeException(
                'Có sản phẩm không còn hợp lệ. Vui lòng kiểm tra lại giỏ hàng.'
            );
        }

        if ($quantity > (int) $variant['stock']) {
            throw new RuntimeException(
                $variant['product_name']
                . ' chỉ còn '
                . $variant['stock']
                . ' sản phẩm.'
            );
        }

        $price = (float) $variant['sale_price'] > 0
            ? (float) $variant['sale_price']
            : (float) $variant['price'];

        $subtotal += $price * $quantity;

        $verifiedItems[] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'product_name' => $variant['product_name'],
            'variant_label' => $variant['color_name'] . ' / ' . $variant['size_name'],
            'sku' => $variant['sku'],
            'quantity' => $quantity,
            'price' => $price,
        ];
    }

    if (empty($verifiedItems)) {
        throw new RuntimeException('Giỏ hàng không có sản phẩm hợp lệ.');
    }

    // Bước 6: Tính tiền và thêm bản ghi đơn hàng.
    $shippingFee = shipping_fee($subtotal);
    $total = $subtotal + $shippingFee;

    $temporaryCode = 'TMP-' . bin2hex(random_bytes(6));
    $paymentStatus = 'unpaid';
    $orderStatus = 'pending';
    $paymentReference = '';

    $insertOrder = $conn->prepare(
        'INSERT INTO orders
        (
            order_code,
            user_id,
            customer_name,
            phone,
            email,
            address,
            note,
            payment_method,
            payment_status,
            subtotal,
            shipping_fee,
            total,
            status,
            payment_reference
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $insertOrder->bind_param(
        'sisssssssdddss',
        $temporaryCode,
        $userId,
        $customerName,
        $phone,
        $email,
        $fullAddress,
        $note,
        $paymentMethod,
        $paymentStatus,
        $subtotal,
        $shippingFee,
        $total,
        $orderStatus,
        $paymentReference
    );
    $insertOrder->execute();

    $orderId = (int) $conn->insert_id;
    $orderCode = create_order_code($orderId);
    $paymentReference = $orderCode;

    $updateOrderCode = $conn->prepare(
        'UPDATE orders SET order_code = ?, payment_reference = ? WHERE id = ?'
    );
    $updateOrderCode->bind_param(
        'ssi',
        $orderCode,
        $paymentReference,
        $orderId
    );
    $updateOrderCode->execute();

    // Bước 7: Lưu chi tiết đơn và trừ tồn kho từng biến thể.
    $insertOrderItem = $conn->prepare(
        'INSERT INTO order_items
        (
            order_id,
            product_id,
            variant_id,
            product_name,
            variant_label,
            sku,
            quantity,
            price
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $decreaseStock = $conn->prepare(
        'UPDATE product_variants
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?'
    );

    foreach ($verifiedItems as $item) {
        $insertOrderItem->bind_param(
            'iiisssid',
            $orderId,
            $item['product_id'],
            $item['variant_id'],
            $item['product_name'],
            $item['variant_label'],
            $item['sku'],
            $item['quantity'],
            $item['price']
        );
        $insertOrderItem->execute();

        $decreaseStock->bind_param(
            'iii',
            $item['quantity'],
            $item['variant_id'],
            $item['quantity']
        );
        $decreaseStock->execute();

        if ($decreaseStock->affected_rows !== 1) {
            throw new RuntimeException(
                'Tồn kho vừa thay đổi, vui lòng đặt hàng lại.'
            );
        }
    }

    // Tất cả thao tác thành công thì xác nhận transaction.
    $conn->commit();
    unset($_SESSION['cart']);

    // Bước 8: Nếu chọn ZaloPay thì tạo phiên thanh toán Sandbox.
    if ($paymentMethod === 'zalopay') {
        $orderModel = new OrderModel($conn);
        $zalopayService = new ZaloPayPaymentService();

        try {
            $savedOrder = $orderModel->getOrderById($orderId);

            if (!$savedOrder) {
                throw new RuntimeException(
                    'Không tìm thấy đơn vừa tạo để thanh toán ZaloPay.'
                );
            }

            $paymentSession = $zalopayService->createPayment(
                $savedOrder,
                $verifiedItems
            );

            $orderModel->saveZaloPaySession($orderId, $paymentSession);

            // ZaloPay mở cổng thanh toán và hiển thị QR đúng số tiền.
            redirect($paymentSession['zalopay_order_url']);
        } catch (Throwable $paymentError) {
            $orderModel->markZaloPayCreateError(
                $orderId,
                $paymentError->getMessage()
            );

            flash(
                'payment',
                public_error_message(
                    $paymentError,
                    'Không tạo được phiên thanh toán ZaloPay. '
                    . 'Bạn có thể thử lại trong chi tiết đơn hàng.'
                ),
                'error'
            );
        }
    }

    redirect('success.php?id=' . $orderId);
} catch (Throwable $error) {
    try {
        $conn->rollback();
    } catch (Throwable) {
        // Chưa bắt đầu transaction hoặc kết nối đã đóng.
    }

    flash('client', public_error_message($error), 'error');
    redirect('checkout.php');
}
