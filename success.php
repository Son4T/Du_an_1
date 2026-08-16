<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/OrderModel.php';
require_client('login.php');
$id = (int) ($_GET['id'] ?? 0);
$model = new OrderModel($conn);
$order = $model->getClientOrder($id, (int) $_SESSION['client_user_id']);
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}
$items = $model->getItems($id);
$paymentNotice = flash('payment');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>Đặt hàng thành công</title>
    <link href="style.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  </head>
  <body>
    <?php include 'includes/header.php'; ?>
    <main class="section alt">
      <div class="container" style="max-width:900px">
        <?php if ($paymentNotice): ?>
        <div class="alert alert-<?= e($paymentNotice['type']) ?>">
          <?= e($paymentNotice['message']) ?>
        </div>
        <?php endif; ?>
        <section class="card" style="text-align:center">
          <div style="width:70px;height:70px;border-radius:50%;background:#dcfce7;color:#16a34a;display:grid;place-items:center;font-size:30px;margin:auto">
            <i class="fa-solid fa-check"></i>
          </div>
          <h1>Đặt hàng thành công!</h1>
          <p class="text-muted">
            Mã đơn hàng của bạn là
            <strong><?= e($order['order_code']) ?></strong>
            .
          </p>
          <span class="badge <?= e($order['status']) ?>">
            <?= e(order_status_text($order['status'])) ?>
          </span>
        </section>
        <?php if ($order['payment_method'] === 'zalopay'): ?>
        <section class="card qr-card" id="paymentCard" style="margin-top:20px">
          <div <?= $order['payment_status'] === 'paid' ? '' : 'hidden' ?> id="paidSuccess">
            <div style="width:76px;height:76px;border-radius:50%;background:#dcfce7;color:#15803d;display:grid;place-items:center;font-size:34px;margin:0 auto 12px">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <h2 style="color:#15803d">Thanh toán thành công</h2>
            <p>
              ZaloPay đã xác nhận giao dịch cho đơn
              <strong><?= e($order['order_code']) ?></strong>
              .
            </p>
            <?php if (!empty($order['zalopay_zp_trans_id'])): ?>
            <p class="text-muted">
              Mã giao dịch ZaloPay:
              <strong><?= e($order['zalopay_zp_trans_id']) ?></strong>
            </p>
            <?php endif; ?>
          </div>
          <div <?= $order['payment_status'] === 'paid' ? 'hidden' : '' ?> id="unpaidBox">
            <h2>Thanh toán qua ZaloPay Sandbox</h2>
            <p>
              Mở trang thanh toán ZaloPay để quét QR. Số tiền được lấy
              trực tiếp từ đơn hàng nên khách không phải nhập lại.
            </p>
            <div class="summary-line">
              <span>Mã đơn</span>
              <strong><?= e($order['order_code']) ?></strong>
            </div>
            <div class="summary-line total">
              <span>Số tiền cần thanh toán</span>
              <strong class="text-danger"><?= money($order['total']) ?></strong>
            </div>
            <?php if (!empty($order['zalopay_order_url'])): ?>
            <a
              class="btn btn-primary"
              href="<?= e($order['zalopay_order_url']) ?>"
              style="width:100%;margin-top:10px"
            >
              <i class="fa-solid fa-qrcode"></i>
              Mở QR thanh toán ZaloPay
            </a>
            <?php else: ?>
            <form action="zalopay_create.php" method="post">
              <?= csrf_input() ?>
              <input name="id" type="hidden" value="<?= $id ?>"/>
              <button class="btn btn-primary" style="width:100%;margin-top:10px">
                <i class="fa-solid fa-rotate"></i>
                Tạo lại phiên thanh toán
              </button>
            </form>
            <?php endif; ?>
            <div
              class="alert"
              id="waitingPayment"
              style="margin-top:16px;background:#fef3c7;color:#92400e"
            >
              <i class="fa-solid fa-spinner fa-spin"></i>
              Đang chờ ZaloPay xác nhận… Trang sẽ tự kiểm tra trạng thái.
            </div>
            <?php if (!empty($order['payment_message'])): ?>
            <p class="text-muted">
              <small><?= e($order['payment_message']) ?></small>
            </p>
            <?php endif; ?>
          </div>
        </section>
        <?php endif; ?>
        <section class="card" style="margin-top:20px">
          <h3>Tóm tắt đơn hàng</h3>
          <?php foreach ($items as $item): ?>
          <div class="summary-line">
            <span>
              <?= e($item['product_name']) ?>
              <br/>
              <small>
                <?= e($item['variant_label']) ?>
                ×
                <?= (int) $item['quantity'] ?>
              </small>
            </span>
            <strong><?= money($item['price'] * $item['quantity']) ?></strong>
          </div>
          <?php endforeach; ?>
          <div class="summary-line total">
            <span>Tổng thanh toán</span>
            <strong class="text-danger"><?= money($order['total']) ?></strong>
          </div>
          <div class="hero-actions" style="justify-content:center">
            <a class="btn btn-primary" href="order_detail.php?id=<?= $id ?>">
              Xem chi tiết đơn
            </a>
            <a class="btn btn-outline" href="products.php">Tiếp tục mua</a>
          </div>
        </section>
      </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    <?php
if ($order['payment_method'] === 'zalopay' && $order['payment_status'] !== 'paid'):
?>
    <script>
      (() => {
        const paidBox = document.getElementById('paidSuccess');
        const unpaidBox = document.getElementById('unpaidBox');
        let stopped = false;

        async function checkPayment() {
          if (stopped) {
            return;
          }

          try {
            const url = 'payment_status.php?id=<?= $id ?>&t=' + Date.now();
            const response = await fetch(url, {
              credentials: 'same-origin',
              cache: 'no-store'
            });

            if (!response.ok) {
              return;
            }

            const data = await response.json();

            if (data.ok && data.payment_status === 'paid') {
              stopped = true;
              unpaidBox.hidden = true;
              paidBox.hidden = false;
              paidBox.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
              });
              document.title = 'Thanh toán thành công - <?= e($order['order_code']) ?>';
            }
          } catch (error) {
            console.warn(
              'Chưa kiểm tra được trạng thái thanh toán.',
              error
            );
          }
        }

        checkPayment();

        const timer = setInterval(() => {
          checkPayment();

          if (stopped) {
            clearInterval(timer);
          }
        }, 5000);
      })();
    </script>
    <?php endif; ?>
  </body>
</html>
