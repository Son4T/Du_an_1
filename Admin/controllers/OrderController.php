<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/OrderModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') { http_response_code(403); exit('Bạn không có quyền quản lý đơn hàng.'); }
if (!hash_equals($_SESSION['_nam_admin_order_csrf'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(419); exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang.'); }

$orderId = (int) ($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';
try {
    $model = new OrderModel($conn);
    $model->updateStatus($orderId, $status);
    $_SESSION['_nam_admin_order_flash'] = ['message' => 'Đã cập nhật trạng thái đơn hàng.', 'type' => 'success'];
} catch (Throwable $error) {
    $_SESSION['_nam_admin_order_flash'] = ['message' => $error instanceof RuntimeException ? $error->getMessage() : 'Không thể cập nhật đơn hàng.', 'type' => 'error'];
}
header('Location: ../views/order_detail.php?id=' . $orderId); exit;
