<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/OrderModel.php';
require_once dirname(__DIR__) . '/Admin/services/ZaloPayPaymentService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'return_code' => 2,
        'return_message' => 'Phương thức không hợp lệ',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$requestBody = file_get_contents('php://input');
$requestData = json_decode((string) $requestBody, true);

$data = is_array($requestData)
    ? (string) ($requestData['data'] ?? '')
    : '';
$receivedMac = is_array($requestData)
    ? (string) ($requestData['mac'] ?? '')
    : '';
$callbackType = is_array($requestData)
    ? (int) ($requestData['type'] ?? 0)
    : 0;

if ($callbackType !== 1) {
    echo json_encode([
        'return_code' => 2,
        'return_message' => 'Loại callback không hợp lệ',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$zalopayService = new ZaloPayPaymentService();

if (!$zalopayService->verifyCallback($data, $receivedMac)) {
    error_log('ZaloPay callback bị từ chối vì chữ ký không hợp lệ.');

    echo json_encode([
        'return_code' => 2,
        'return_message' => 'Dữ liệu callback không hợp lệ',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$callbackData = json_decode($data, true);

if (
    !is_array($callbackData)
    || (int) ($callbackData['app_id'] ?? 0) !== $zalopayService->appId()
) {
    echo json_encode([
        'return_code' => 2,
        'return_message' => 'AppID không hợp lệ',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$orderModel = new OrderModel($conn);

if (!$orderModel->applyZaloPayCallback($callbackData)) {
    echo json_encode([
        'return_code' => 2,
        'return_message' => 'Không cập nhật được đơn hàng',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'return_code' => 1,
    'return_message' => 'Thành công',
], JSON_UNESCAPED_UNICODE);
