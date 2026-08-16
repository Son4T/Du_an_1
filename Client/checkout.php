<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/ProductModel.php';
require_once dirname(__DIR__) . '/Admin/models/UserModel.php';
require_client('login.php');
$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    flash('client', 'Giỏ hàng đang trống.', 'error');
    redirect('cart.php');
}
$productModel = new ProductModel($conn);
$user = (new UserModel($conn))->find((int) $_SESSION['client_user_id']);
if (!$user || $user['status'] !== 'active') {
    unset($_SESSION['client_user_id'], $_SESSION['client_username'], $_SESSION['client_name']);
    flash('auth', 'Tài khoản không còn khả dụng. Vui lòng đăng nhập lại.', 'error');
    redirect('login.php');
}
$items = [];
$subtotal = 0;
foreach ($cart as $row) {
    $productId = (int) ($row['product_id'] ?? 0);
    $variantId = (int) ($row['variant_id'] ?? 0);
    $quantity = (int) ($row['quantity'] ?? 0);
    $product = $productModel->getProductById($productId, true);
    $variant = $productModel->getVariantById($variantId);
    if ( !$product || !$variant || (int) $variant['product_id'] !== $productId || $variant['product_status'] !== 'Hiện' || $quantity < 1 || $quantity > (int) $variant['stock'] ) {
        flash('client', 'Một sản phẩm đã hết hàng hoặc thay đổi tồn kho. Vui lòng kiểm tra lại giỏ.', 'error');
        redirect('cart.php');
    }
    $price = (float) $product['sale_price'] > 0 ? (float) $product['sale_price'] : (float) $variant['price'];
    $lineTotal = $price * $quantity;
    $subtotal += $lineTotal;
    $items[] = [ 'product' => $product, 'variant' => $variant, 'quantity' => $quantity, 'line' => $lineTotal, ];
}
$shippingFee = shipping_fee($subtotal);
$notice = flash('client');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>
      Thanh toán -
      <?= e(STORE_NAME) ?>
    </title>
    <link href="style.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  </head>
  <body>
    <?php include 'includes/header.php'; ?>
    <section class="page-hero">
      <div class="container">
        <h1>Thanh toán</h1>
        <p>Nhập chính xác thông tin để cửa hàng giao hàng cho bạn.</p>
      </div>
    </section>
    <main class="section alt">
      <div class="container">
        <?php if ($notice): ?>
        <div class="alert alert-<?= e($notice['type']) ?>">
          <?= e($notice['message']) ?>
        </div>
        <?php endif; ?>
        <form action="place_order.php" class="checkout-layout" id="checkoutForm" method="post">
          <section class="card">
            <?= csrf_input() ?>
            <h2>Thông tin nhận hàng</h2>
            <div class="form-grid">
              <div class="field">
                <label>Họ và tên *</label>
                <input autocomplete="name" maxlength="120" minlength="2" name="customer_name" required="" value="<?= e($user['full_name']) ?>"/>
              </div>
              <div class="field">
                <label>Số điện thoại *</label>
                <input autocomplete="tel" inputmode="numeric" maxlength="10" name="phone" pattern="0[0-9]{9}" required="" value="<?= e($user['phone']) ?>"/>
              </div>
              <div class="field full">
                <label>Email *</label>
                <input autocomplete="email" maxlength="120" name="email" required="" type="email" value="<?= e($user['email']) ?>"/>
              </div>
              <div class="field">
                <label>Tỉnh/Thành phố *</label>
                <input autocomplete="address-level1" maxlength="100" name="province" placeholder="Hà Nội" required=""/>
              </div>
              <div class="field">
                <label>Quận/Huyện *</label>
                <input autocomplete="address-level2" maxlength="100" name="district" required=""/>
              </div>
              <div class="field">
                <label>Phường/Xã *</label>
                <input autocomplete="address-level3" maxlength="100" name="ward" required=""/>
              </div>
              <div class="field">
                <label>Số nhà, tên đường *</label>
                <input autocomplete="street-address" maxlength="200" name="address_detail" required="" value="<?= e($user['address']) ?>"/>
              </div>
              <div class="field full">
                <label>Ghi chú</label>
                <textarea maxlength="1000" name="note" placeholder="Thời gian nhận hàng, yêu cầu đóng gói..." rows="3"></textarea>
              </div>
            </div>
            <h2>Phương thức thanh toán</h2>

            <label class="payment-option">
              <input
                checked
                name="payment_method"
                type="radio"
                value="cod"
              />

              <span class="payment-content">
                <strong>Thanh toán khi nhận hàng (COD)</strong>
                <small class="text-muted">
                  Thanh toán tiền mặt cho nhân viên giao hàng.
                </small>
              </span>
            </label>

            <label class="payment-option">
              <input
                name="payment_method"
                type="radio"
                value="zalopay"
              />

              <span class="payment-content">
                <strong>Thanh toán ZaloPay Sandbox bằng QR</strong>
                <small class="text-muted">
                  ZaloPay Sandbox tạo QR đúng số tiền. Website tự kiểm tra
                  và cập nhật “Đã thanh toán” sau giao dịch.
                </small>
              </span>
            </label>
            <button class="btn btn-primary" style="width:100%;margin-top:18px" type="submit">Đặt hàng</button>
          </section>
          <aside class="card" style="height:max-content">
            <h3>Đơn hàng của bạn</h3>
            <?php foreach ($items as $item): ?>
            <div class="summary-line">
              <span>
                <?= e($item['product']['name']) ?>
                <br/>
                <small>
                  <?= e($item['variant']['color_name'] . ' / ' . $item['variant']['size_name']) ?>
                  ×
                  <?= (int) $item['quantity'] ?>
                </small>
              </span>
              <strong><?= money($item['line']) ?></strong>
            </div>
            <?php endforeach; ?>
            <hr style="border:0;border-top:1px solid #e2e8f0"/>
            <div class="summary-line">
              <span>Tạm tính</span>
              <strong><?= money($subtotal) ?></strong>
            </div>
            <div class="summary-line">
              <span>Vận chuyển</span>
              <strong><?= money($shippingFee) ?></strong>
            </div>
            <div class="summary-line total">
              <strong>Tổng cộng</strong>
              <strong class="text-danger"><?= money($subtotal + $shippingFee) ?></strong>
            </div>
          </aside>
        </form>
      </div>
    </main>
    <?php include 'includes/footer.php'; ?>
  </body>
</html>
