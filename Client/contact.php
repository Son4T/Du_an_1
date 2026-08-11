<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
$notice = flash('client');
$user = ['full_name' => '', 'email' => '', 'phone' => ''];
if (!empty($_SESSION['client_user_id'])) {
    $statement = $conn->prepare('SELECT full_name, email, phone FROM users WHERE id = ?');
    $statement->bind_param('i', $_SESSION['client_user_id']);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc() ?: $user;
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liên hệ - <?= e(STORE_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/store.css?v=20260812">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
      .contact-hero{background:#111827;color:#fff;padding:64px 0}.contact-hero h1{font-size:42px;margin:8px 0}.contact-hero p{color:#cbd5e1;margin:0;font-size:17px}.contact-section{padding:64px 0}.contact-layout{display:grid;grid-template-columns:.82fr 1.18fr;gap:28px}.contact-info{background:#111827;color:#fff;border-radius:18px;padding:30px}.contact-info h2{margin-top:0}.contact-info>p{color:#cbd5e1;line-height:1.7}.contact-item{display:flex;gap:14px;padding:18px 0;border-bottom:1px solid #334155}.contact-item:last-child{border-bottom:0}.contact-item i{color:#fb7185;font-size:18px;margin-top:3px}.contact-item strong,.contact-item span{display:block}.contact-item span{color:#cbd5e1;margin-top:4px}.contact-form{border:1px solid #e2e8f0;border-radius:18px;padding:30px;background:#fff}.contact-form h2{margin-top:0}.contact-form .form-grid{grid-template-columns:1fr 1fr}.contact-form textarea{resize:vertical}.contact-alert{margin-bottom:18px;padding:13px 15px;border-radius:10px}.contact-alert.success{background:#dcfce7;color:#166534}.contact-alert.error{background:#fee2e2;color:#991b1b}@media(max-width:760px){.contact-section{padding:44px 0}.contact-layout,.contact-form .form-grid{grid-template-columns:1fr}.contact-hero h1{font-size:35px}}
    </style>
  </head>
  <body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <section class="contact-hero"><div class="container"><span class="eyebrow">HỖ TRỢ KHÁCH HÀNG</span><h1>Liên hệ với Urban Tribe</h1><p>Chúng tôi sẽ phản hồi yêu cầu của bạn trong thời gian sớm nhất.</p></div></section>
    <main class="contact-section"><div class="container contact-layout"><aside class="contact-info"><h2>Thông tin liên hệ</h2><p>Đặt câu hỏi về sản phẩm, đơn hàng hoặc góp ý để Urban Tribe phục vụ bạn tốt hơn.</p><div class="contact-item"><i class="fa-solid fa-location-dot"></i><div><strong>Địa chỉ</strong><span><?= e(STORE_ADDRESS) ?></span></div></div><div class="contact-item"><i class="fa-solid fa-phone"></i><div><strong>Điện thoại</strong><span><?= e(STORE_PHONE) ?></span></div></div><div class="contact-item"><i class="fa-solid fa-envelope"></i><div><strong>Email</strong><span><?= e(STORE_EMAIL) ?></span></div></div><div class="contact-item"><i class="fa-solid fa-clock"></i><div><strong>Thời gian hỗ trợ</strong><span>Thứ Hai – Thứ Bảy, 08:00 – 21:00</span></div></div></aside>
      <section class="contact-form"><h2>Gửi tin nhắn</h2><p class="text-muted">Các trường có dấu * là bắt buộc.</p><?php if ($notice): ?><div class="contact-alert <?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?><form method="post" action="contact_action.php"><?= csrf_input() ?><div class="form-grid"><div class="field"><label>Họ và tên *</label><input required minlength="2" name="full_name" value="<?= e($user['full_name']) ?>"></div><div class="field"><label>Email *</label><input required name="email" type="email" value="<?= e($user['email']) ?>"></div></div><div class="form-grid" style="margin-top:14px"><div class="field"><label>Số điện thoại</label><input name="phone" pattern="[0-9+ ]{9,20}" value="<?= e($user['phone']) ?>"></div><div class="field"><label>Chủ đề *</label><input required minlength="3" name="subject" placeholder="Ví dụ: Hỗ trợ đơn hàng"></div></div><div class="field" style="margin-top:14px"><label>Nội dung *</label><textarea required maxlength="2000" minlength="10" name="message" rows="7" placeholder="Hãy cho chúng tôi biết bạn cần hỗ trợ gì..."></textarea></div><button class="btn btn-primary" style="margin-top:18px" type="submit"><i class="fa-solid fa-paper-plane"></i> Gửi liên hệ</button></form></section>
    </div></main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
  </body>
</html>
