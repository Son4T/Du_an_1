<?php
require_once __DIR__ . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/Admin/config/database.php';
require_once dirname(__DIR__) . '/Admin/models/OrderModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { nam_cart_redirect('orders.php'); }
nam_cart_verify_csrf();
$orderId = (int) ($_POST['order_id'] ?? 0);
$userId = nam_cart_current_user_id();
if (!$userId || $orderId <= 0) { nam_cart_redirect('orders.php'); }

try {
    (new OrderModel($conn))->cancelPendingForUser($orderId, $userId);
    nam_cart_flash('Đã hủy đơn hàng và hoàn lại tồn kho.');
} catch (Throwable $error) {
    nam_cart_flash($error instanceof RuntimeException ? $error->getMessage() : 'Không thể hủy đơn hàng.', 'error');
}
nam_cart_redirect('order_detail.php?id=' . $orderId);
