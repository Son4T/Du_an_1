<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/OrderModel.php';
require_once dirname(__DIR__) . '/Admin/services/ZaloPayPaymentService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['client_user_id'])) {
    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'message' => 'Phiên đăng nhập đã hết hạn.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$orderId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['client_user_id'];
$orderModel = new OrderModel($conn);
$order = $orderModel->getClientOrder($orderId, $userId);

if (!$order) {
    http_response_code(404);

    echo json_encode([
        'ok' => false,
        'message' => 'Không tìm thấy đơn hàng.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
 * Nếu chưa có callback, website chủ động hỏi ZaloPay.
 * Cách này vẫn dùng được khi dự án đang chạy trên localhost.
 */
$canQueryZaloPay = $order['payment_method'] === 'zalopay'
    && $order['payment_status'] !== 'paid'
    && !empty($order['zalopay_app_trans_id']);

if ($canQueryZaloPay) {
    $lastUpdate = !empty($order['payment_updated_at'])
        ? strtotime($order['payment_updated_at'])
        : 0;

    // Tránh gọi API quá dày khi trang đang tự kiểm tra liên tục.
    if (time() - $lastUpdate >= 4) {
        try {
            $zalopayService = new ZaloPayPaymentService();
            $queryResult = $zalopayService->queryPayment(
                (string) $order['zalopay_app_trans_id']
            );

            $orderModel->applyZaloPayQueryResult(
                (string) $order['zalopay_app_trans_id'],
                $queryResult
            );

            $order = $orderModel->getClientOrder($orderId, $userId);
        } catch (Throwable $error) {
            error_log($error->getMessage());
        }
    }
}

echo json_encode([
    'ok' => true,
    'payment_status' => $order['payment_status'],
    'payment_status_text' => payment_status_text(
        $order['payment_status']
    ),
    'order_status' => $order['status'],
    'order_status_text' => order_status_text($order['status']),
    'message' => $order['payment_message'] ?? '',
    'trans_id' => $order['zalopay_zp_trans_id'] ?? '',
    'paid_at' => $order['paid_at'] ?? null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
