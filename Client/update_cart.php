<?php
require_once __DIR__ . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/Admin/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    nam_cart_redirect('cart.php');
}

nam_cart_verify_csrf();
$quantities = $_POST['quantities'] ?? [];
if (!is_array($quantities)) {
    nam_cart_redirect('cart.php');
}

foreach ($quantities as $variantId => $quantity) {
    $variantId = (int) $variantId;
    $quantity = (int) $quantity;
    if ($variantId <= 0) {
        continue;
    }
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$variantId]);
        continue;
    }

    $statement = $conn->prepare('SELECT stock FROM product_variants WHERE id = ?');
    $statement->bind_param('i', $variantId);
    $statement->execute();
    $variant = $statement->get_result()->fetch_assoc();
    if (!$variant || (int) $variant['stock'] < 1) {
        unset($_SESSION['cart'][$variantId]);
        continue;
    }
    $_SESSION['cart'][$variantId] = min($quantity, (int) $variant['stock'], 99);
}

nam_cart_flash('Đã cập nhật giỏ hàng.');
nam_cart_redirect('cart.php');
