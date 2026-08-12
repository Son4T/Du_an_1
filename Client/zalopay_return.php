<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/OrderModel.php';
require_once dirname(__DIR__) . '/Admin/services/ZaloPayPaymentService.php';

$orderId = (int) ($_GET['id'] ?? 0);
$orderModel = new OrderModel($conn);
$order = $orderModel->getOrderById($orderId);

if (!$order || $order['payment_method'] !== 'zalopay') {
    flash('payment', 'Không tìm thấy đơn thanh toán ZaloPay.', 'error');
    redirect('order_history.php');
}

if (
    $order['payment_status'] !== 'paid'
    && !empty($order['zalopay_app_trans_id'])
) {
    try {
        $zalopayService = new ZaloPayPaymentService();
        $queryResult = $zalopayService->queryPayment(
            (string) $order['zalopay_app_trans_id']
        );

        $orderModel->applyZaloPayQueryResult(
            (string) $order['zalopay_app_trans_id'],
            $queryResult
        );
    } catch (Throwable $error) {
        error_log($error->getMessage());
    }
}

$order = $orderModel->getOrderById($orderId);

if ($order && $order['payment_status'] === 'paid') {
    flash(
        'payment',
        'Thanh toán ZaloPay thành công. Đơn hàng đã được cập nhật.',
        'success'
    );
} else {
    flash(
        'payment',
        'Giao dịch chưa được ZaloPay xác nhận. Hệ thống sẽ tiếp tục kiểm tra.',
        'error'
    );
}

if (
    empty($_SESSION['client_user_id'])
    || (int) $_SESSION['client_user_id'] !== (int) $order['user_id']
) {
    $_SESSION['redirect_after_login'] = 'success.php?id=' . $orderId;
    redirect('login.php');
}

redirect('success.php?id=' . $orderId);
