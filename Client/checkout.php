<?php
require_once __DIR__ . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/Admin/config/database.php';
$items = nam_cart_items($conn);
if (!$items) {
    nam_cart_flash('Giỏ hàng đang trống, chưa thể thanh toán.', 'error');
    nam_cart_redirect('cart.php');
}
$summary = nam_cart_summary($items);
$user = ['full_name' => '', 'phone' => '', 'email' => '', 'address' => ''];
if ($userId = nam_cart_current_user_id()) {
    $statement = $conn->prepare('SELECT full_name, phone, email, address FROM users WHERE id = ?');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc() ?: $user;
}
require __DIR__ . '/includes/header.php';
?>
<style>
.nam-checkout{max-width:1050px;margin:36px auto;padding:0 20px}.nam-checkout-grid{display:grid;grid-template-columns:1fr 340px;gap:22px}.nam-box{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px}.nam-field{margin:13px 0}.nam-field label{display:block;font-weight:600;margin-bottom:6px}.nam-field input,.nam-field textarea,.nam-field select{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font:inherit}.nam-summary-item{padding:11px 0;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:10px}.nam-total-line{font-size:20px;font-weight:700;display:flex;justify-content:space-between;margin-top:17px}.nam-submit{background:#111827;border:0;border-radius:8px;color:#fff;padding:12px 17px;font-weight:700;cursor:pointer;width:100%;margin-top:20px}@media(max-width:760px){.nam-checkout-grid{grid-template-columns:1fr}}
</style>
<section class="nam-checkout"><h1>Thanh toán</h1><div class="nam-checkout-grid">
  <form class="nam-box" method="post" action="place_order.php">
    <?= nam_cart_csrf_input() ?><h2>Thông tin nhận hàng</h2>
    <div class="nam-field"><label>Họ và tên *</label><input required minlength="2" name="customer_name" value="<?= nam_cart_e($user['full_name']) ?>"></div>
    <div class="nam-field"><label>Số điện thoại *</label><input required name="phone" pattern="[0-9+ ]{9,20}" value="<?= nam_cart_e($user['phone']) ?>"></div>
    <div class="nam-field"><label>Email</label><input name="email" type="email" value="<?= nam_cart_e($user['email']) ?>"></div>
    <div class="nam-field"><label>Địa chỉ nhận hàng *</label><textarea required minlength="10" name="address" rows="3"><?= nam_cart_e($user['address']) ?></textarea></div>
    <div class="nam-field"><label>Ghi chú</label><textarea name="note" maxlength="1000" rows="3" placeholder="Ví dụ: giao giờ hành chính"></textarea></div>
    <div class="nam-field"><label>Phương thức thanh toán</label><select name="payment_method"><option value="cod">Thanh toán khi nhận hàng (COD)</option></select></div>
    <button class="nam-submit">Đặt hàng · <?= nam_cart_money($summary['total']) ?></button>
  </form>
  <aside class="nam-box"><h2>Đơn hàng của bạn</h2><?php foreach ($items as $item): ?><div class="nam-summary-item"><span><?= nam_cart_e($item['product_name']) ?><br><small><?= nam_cart_e($item['variant_label']) ?> × <?= (int) $item['quantity'] ?></small></span><strong><?= nam_cart_money($item['line_total']) ?></strong></div><?php endforeach; ?><div class="nam-summary-item"><span>Phí vận chuyển</span><strong><?= nam_cart_money($summary['shipping']) ?></strong></div><div class="nam-total-line"><span>Tổng cộng</span><span><?= nam_cart_money($summary['total']) ?></span></div></aside>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
