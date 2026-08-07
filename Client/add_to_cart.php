<?php
require_once __DIR__ . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/Admin/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    nam_cart_redirect('products.php');
}

nam_cart_verify_csrf();
$variantId = (int) ($_POST['variant_id'] ?? 0);
$quantity = max(1, min(99, (int) ($_POST['quantity'] ?? 1)));

if ($variantId <= 0) {
    nam_cart_flash('Vui lòng chọn biến thể sản phẩm.', 'error');
    nam_cart_redirect('products.php');
}

$statement = $conn->prepare(
    "SELECT pv.stock, p.status FROM product_variants pv
     INNER JOIN products p ON p.id = pv.product_id WHERE pv.id = ?"
);
$statement->bind_param('i', $variantId);
$statement->execute();
$variant = $statement->get_result()->fetch_assoc();

if (!$variant || $variant['status'] !== 'Hiện' || (int) $variant['stock'] < 1) {
    nam_cart_flash('Sản phẩm này hiện không còn hàng.', 'error');
    nam_cart_redirect('products.php');
}

$current = (int) ($_SESSION['cart'][$variantId] ?? 0);
$_SESSION['cart'][$variantId] = min($current + $quantity, (int) $variant['stock'], 99);
nam_cart_flash('Đã thêm sản phẩm vào giỏ hàng.');
nam_cart_redirect('cart.php');
