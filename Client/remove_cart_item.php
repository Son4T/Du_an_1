<?php
require_once __DIR__ . '/includes/cart_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    nam_cart_verify_csrf();
    $variantId = (int) ($_POST['variant_id'] ?? 0);
    unset($_SESSION['cart'][$variantId]);
    nam_cart_flash('Đã xóa sản phẩm khỏi giỏ hàng.');
}

nam_cart_redirect('cart.php');
