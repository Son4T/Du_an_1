<?php
require_once __DIR__ . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/Admin/config/database.php';
$items = nam_cart_items($conn);
$summary = nam_cart_summary($items);
$notice = nam_cart_flash();
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/css/cart.css">
<style>
.nam-cart{max-width:1100px;margin:36px auto;padding:0 20px}.nam-cart-grid{display:grid;grid-template-columns:1fr 320px;gap:22px}.nam-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:20px}.nam-cart-item{display:grid;grid-template-columns:78px 1fr 110px 110px 42px;gap:15px;align-items:center;padding:14px 0;border-bottom:1px solid #eee}.nam-cart-item img{width:78px;height:90px;object-fit:cover;border-radius:9px;background:#f3f4f6}.nam-qty{width:68px;padding:8px;border:1px solid #d1d5db;border-radius:7px}.nam-alert{padding:12px 14px;border-radius:9px;margin-bottom:16px}.nam-alert.success{background:#dcfce7;color:#166534}.nam-alert.error{background:#fee2e2;color:#991b1b}.nam-total{font-size:20px;font-weight:700}.nam-row{display:flex;justify-content:space-between;gap:12px;margin:12px 0}.nam-btn{display:inline-block;border:0;border-radius:8px;padding:10px 14px;background:#111827;color:#fff;font-weight:600;cursor:pointer}.nam-btn.secondary{background:#e5e7eb;color:#111827}.nam-btn.danger{background:#fee2e2;color:#b91c1c}.nam-empty{text-align:center;padding:45px 15px;color:#6b7280}@media(max-width:760px){.nam-cart-grid{grid-template-columns:1fr}.nam-cart-item{grid-template-columns:60px 1fr 70px}.nam-cart-item .nam-price,.nam-cart-item .nam-remove{grid-column:2}.nam-cart-item img{width:60px;height:70px}}
</style>
<section class="nam-cart">
  <h1>Giỏ hàng của bạn</h1>
  <?php if ($notice): ?><div class="nam-alert <?= nam_cart_e($notice['type']) ?>"><?= nam_cart_e($notice['message']) ?></div><?php endif; ?>
  <?php if (!$items): ?>
    <div class="nam-card nam-empty"><h2>Giỏ hàng đang trống</h2><p>Hãy chọn những sản phẩm bạn yêu thích để bắt đầu mua sắm.</p><a class="nam-btn" href="products.php">Xem sản phẩm</a></div>
  <?php else: ?>
  <div class="nam-cart-grid">
    <form class="nam-card" method="post" action="update_cart.php">
      <?= nam_cart_csrf_input() ?>
      <?php foreach ($items as $item): ?>
      <div class="nam-cart-item">
        <img src="<?= nam_cart_e(nam_cart_image($item['image_url'])) ?>" alt="<?= nam_cart_e($item['product_name']) ?>">
        <div><strong><?= nam_cart_e($item['product_name']) ?></strong><br><small><?= nam_cart_e($item['variant_label']) ?> · Còn <?= (int) $item['stock'] ?></small></div>
        <input class="nam-qty" min="0" max="<?= (int) $item['stock'] ?>" name="quantities[<?= (int) $item['variant_id'] ?>]" type="number" value="<?= (int) $item['quantity'] ?>">
        <strong class="nam-price"><?= nam_cart_money($item['line_total']) ?></strong>
        <div class="nam-remove"><button class="nam-btn danger" form="remove-<?= (int) $item['variant_id'] ?>" title="Xóa" type="submit">×</button></div>
      </div>
      <?php endforeach; ?>
      <p><button class="nam-btn secondary">Cập nhật giỏ hàng</button></p>
    </form>
    <?php foreach ($items as $item): ?>
    <form id="remove-<?= (int) $item['variant_id'] ?>" method="post" action="remove_cart_item.php"><input type="hidden" name="variant_id" value="<?= (int) $item['variant_id'] ?>"><?= nam_cart_csrf_input() ?></form>
    <?php endforeach; ?>
    <aside class="nam-card"><h2>Tóm tắt đơn hàng</h2><div class="nam-row"><span>Tạm tính</span><strong><?= nam_cart_money($summary['subtotal']) ?></strong></div><div class="nam-row"><span>Phí vận chuyển</span><strong><?= nam_cart_money($summary['shipping']) ?></strong></div><hr><div class="nam-row nam-total"><span>Tổng cộng</span><span><?= nam_cart_money($summary['total']) ?></span></div><a class="nam-btn" style="display:block;text-align:center;margin-top:18px" href="checkout.php">Tiến hành thanh toán</a></aside>
  </div>
<?php endif; ?>
</section>
<script src="assets/js/cart.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
