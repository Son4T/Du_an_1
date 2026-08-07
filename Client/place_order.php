<?php
require_once __DIR__ . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/Admin/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    nam_cart_redirect('cart.php');
}
nam_cart_verify_csrf();

$customerName = trim($_POST['customer_name'] ?? '');
$phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '') ?? '';
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$note = trim($_POST['note'] ?? '');
$paymentMethod = ($_POST['payment_method'] ?? 'cod') === 'cod' ? 'cod' : 'cod';

if (mb_strlen($customerName) < 2 || strlen($phone) < 9 || mb_strlen($address) < 10) {
    nam_cart_flash('Vui lòng nhập đầy đủ họ tên, số điện thoại và địa chỉ.', 'error');
    nam_cart_redirect('checkout.php');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    nam_cart_flash('Email chưa đúng định dạng.', 'error');
    nam_cart_redirect('checkout.php');
}

$items = nam_cart_items($conn);
if (!$items) {
    nam_cart_flash('Giỏ hàng đang trống.', 'error');
    nam_cart_redirect('cart.php');
}

try {
    $conn->begin_transaction();
    $lockedItems = [];
    $subtotal = 0.0;

    foreach ($items as $item) {
        $variantId = (int) $item['variant_id'];
        $statement = $conn->prepare(
            'SELECT pv.id, pv.product_id, pv.sku, pv.stock, pv.price,
                    p.name, p.base_price, p.sale_price, c.color_name, s.size_name
             FROM product_variants pv
             INNER JOIN products p ON p.id = pv.product_id
             INNER JOIN colors c ON c.id = pv.color_id
             INNER JOIN sizes s ON s.id = pv.size_id
             WHERE pv.id = ? FOR UPDATE'
        );
        $statement->bind_param('i', $variantId);
        $statement->execute();
        $variant = $statement->get_result()->fetch_assoc();
        if (!$variant || (int) $variant['stock'] < (int) $item['quantity']) {
            throw new RuntimeException('Sản phẩm "' . $item['product_name'] . '" không đủ số lượng tồn kho.');
        }
        $variant['quantity'] = (int) $item['quantity'];
        // Giá khuyến mãi của sản phẩm được ưu tiên; sau đó mới đến giá riêng của biến thể.
        $variant['unit_price'] = (float) ($variant['sale_price'] ?: ($variant['price'] ?: $variant['base_price']));
        $subtotal += $variant['unit_price'] * $variant['quantity'];
        $lockedItems[] = $variant;
    }

    $shipping = $subtotal < FREE_SHIPPING_FROM ? (float) SHIPPING_FEE : 0.0;
    $total = $subtotal + $shipping;
    $temporaryCode = 'TMP-' . bin2hex(random_bytes(8));
    $userId = nam_cart_current_user_id();
    $statement = $conn->prepare(
        "INSERT INTO orders (order_code, user_id, customer_name, phone, email, address, note,
                             payment_method, subtotal, shipping_fee, total, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    $statement->bind_param('sissssssddd', $temporaryCode, $userId, $customerName, $phone, $email,
        $address, $note, $paymentMethod, $subtotal, $shipping, $total);
    $statement->execute();
    $orderId = $conn->insert_id;
    $orderCode = 'UT' . date('ymd') . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
    $statement = $conn->prepare('UPDATE orders SET order_code = ? WHERE id = ?');
    $statement->bind_param('si', $orderCode, $orderId);
    $statement->execute();

    foreach ($lockedItems as $item) {
        $variantId = (int) $item['id'];
        $productId = (int) $item['product_id'];
        $quantity = (int) $item['quantity'];
        $label = $item['color_name'] . ' · ' . $item['size_name'];
        $price = (float) $item['unit_price'];
        $statement = $conn->prepare(
            'INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_label, sku, quantity, price)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('iiisssid', $orderId, $productId, $variantId, $item['name'], $label,
            $item['sku'], $quantity, $price);
        $statement->execute();

        $statement = $conn->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ?');
        $statement->bind_param('ii', $quantity, $variantId);
        $statement->execute();
    }

    $conn->commit();
    unset($_SESSION['cart']);
    nam_cart_flash('Đặt hàng thành công. Mã đơn của bạn là ' . $orderCode . '.');
    nam_cart_redirect('order_detail.php?id=' . $orderId);
} catch (Throwable $error) {
    $conn->rollback();
    nam_cart_flash($error instanceof RuntimeException ? $error->getMessage() : 'Không thể tạo đơn hàng. Vui lòng thử lại.', 'error');
    nam_cart_redirect('checkout.php');
}
