<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/OrderModel.php';
require_once dirname(__DIR__) . '/Admin/services/ZaloPayPaymentService.php';

require_client('login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$orderId = (int) ($_POST['id'] ?? 0);
$orderModel = new OrderModel($conn);
$order = $orderModel->getClientOrder(
    $orderId,
    (int) $_SESSION['client_user_id']
);

if (!$order || $order['payment_method'] !== 'zalopay') {
    flash('client', 'Đơn hàng không hợp lệ.', 'error');
    redirect('order_history.php');
}

if ($order['payment_status'] === 'paid') {
    flash('client', 'Đơn hàng đã được thanh toán.', 'success');
    redirect('order_detail.php?id=' . $orderId);
}

if ($order['status'] === 'cancelled') {
    flash('client', 'Đơn hàng đã hủy nên không thể thanh toán.', 'error');
    redirect('order_detail.php?id=' . $orderId);
}

$zalopayService = new ZaloPayPaymentService();

try {
    $items = $orderModel->getItems($orderId);
    $paymentSession = $zalopayService->createPayment($order, $items);

    $orderModel->saveZaloPaySession($orderId, $paymentSession);
    redirect($paymentSession['zalopay_order_url']);
} catch (Throwable $error) {
    $orderModel->markZaloPayCreateError(
        $orderId,
        $error->getMessage()
    );

    flash(
        'client',
        public_error_message(
            $error,
            'Không tạo được phiên thanh toán ZaloPay.'
        ),
        'error'
    );

    redirect('order_detail.php?id=' . $orderId);
}
