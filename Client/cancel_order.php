<?php
require_once __DIR__ . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/Admin/config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { nam_cart_redirect('orders.php'); }
nam_cart_verify_csrf();
$orderId = (int) ($_POST['order_id'] ?? 0); $userId = nam_cart_current_user_id();
if (!$userId || $orderId <= 0) { nam_cart_redirect('orders.php'); }
try {
    $conn->begin_transaction();
    $statement = $conn->prepare('SELECT status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
    $statement->bind_param('ii', $orderId, $userId); $statement->execute(); $order = $statement->get_result()->fetch_assoc();
    if (!$order || $order['status'] !== 'pending') { throw new RuntimeException('Chỉ có thể hủy đơn đang chờ xác nhận.'); }
    $statement = $conn->prepare('SELECT variant_id, quantity FROM order_items WHERE order_id = ?');
    $statement->bind_param('i', $orderId); $statement->execute(); $items = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($items as $item) { if ($item['variant_id']) { $statement = $conn->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?'); $statement->bind_param('ii', $item['quantity'], $item['variant_id']); $statement->execute(); } }
    $statement = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?"); $statement->bind_param('i', $orderId); $statement->execute();
    $conn->commit(); nam_cart_flash('Đã hủy đơn hàng và hoàn lại tồn kho.');
} catch (Throwable $error) { $conn->rollback(); nam_cart_flash($error instanceof RuntimeException ? $error->getMessage() : 'Không thể hủy đơn hàng.', 'error'); }
nam_cart_redirect('order_detail.php?id=' . $orderId);
