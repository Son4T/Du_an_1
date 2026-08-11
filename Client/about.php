<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
$pageTitle = 'Giới thiệu';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(STORE_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/store.css?v=20260812">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
      .about-hero{background:linear-gradient(120deg,#111827,#29364f);color:#fff;padding:76px 0}.about-hero .eyebrow{color:#fda4af}.about-hero h1{font-size:clamp(36px,5vw,58px);max-width:700px;margin:12px 0}.about-hero p{max-width:630px;color:#dbeafe;font-size:18px;line-height:1.7}.about-section{padding:72px 0}.about-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:46px;align-items:center}.about-grid h2{font-size:34px;margin:0 0 16px}.about-grid p{color:#64748b;line-height:1.8}.about-panel{padding:30px;border-radius:22px;background:linear-gradient(135deg,#fee2e2,#fff7ed);border:1px solid #fecaca}.about-panel i{font-size:38px;color:#e11d48}.about-panel strong{display:block;font-size:25px;margin:20px 0 10px}.value-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.value-card{border:1px solid #e2e8f0;border-radius:16px;padding:24px;background:#fff}.value-card i{display:grid;place-items:center;width:45px;height:45px;border-radius:12px;background:#ffe4e6;color:#e11d48;font-size:19px}.value-card h3{margin:17px 0 8px}.value-card p{color:#64748b;line-height:1.6;margin:0}.about-cta{background:#111827;border-radius:22px;color:#fff;padding:36px;display:flex;justify-content:space-between;gap:24px;align-items:center}.about-cta h2{margin:0 0 8px}.about-cta p{margin:0;color:#cbd5e1}@media(max-width:760px){.about-hero,.about-section{padding:52px 0}.about-grid,.value-grid{grid-template-columns:1fr}.about-cta{align-items:flex-start;flex-direction:column}}
    </style>
  </head>
  <body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <section class="about-hero"><div class="container"><span class="eyebrow">VỀ URBAN TRIBE</span><h1>Thời trang cho nhịp sống thành thị.</h1><p>Urban Tribe chọn những thiết kế trẻ trung, dễ mặc và đủ linh hoạt để đồng hành cùng bạn từ lớp học, công sở đến những buổi gặp gỡ cuối tuần.</p></div></section>
    <main>
      <section class="about-section"><div class="container about-grid"><div><span class="eyebrow">CÂU CHUYỆN CỦA CHÚNG TÔI</span><h2>Đơn giản để bạn tự tin hơn mỗi ngày.</h2><p>Chúng tôi tin rằng một bộ trang phục tốt không cần quá cầu kỳ. Điều quan trọng là chất liệu thoải mái, phom dáng dễ phối và những chi tiết vừa đủ để thể hiện cá tính của người mặc.</p><p>Urban Tribe xây dựng trải nghiệm mua sắm minh bạch: xem rõ biến thể, số lượng còn lại, theo dõi đơn hàng và nhận hỗ trợ nhanh khi cần.</p></div><div class="about-panel"><i class="fa-solid fa-shirt"></i><strong>Phong cách thuộc về bạn</strong><p>Mỗi sản phẩm được tuyển chọn để phối hợp linh hoạt và phù hợp nhiều hoàn cảnh trong ngày.</p></div></div></section>
      <section class="about-section" style="background:#f8fafc"><div class="container"><div class="section-head"><div><h2>Điều chúng tôi theo đuổi</h2><p>Một trải nghiệm mua sắm rõ ràng, an tâm và gần gũi.</p></div></div><div class="value-grid"><article class="value-card"><i class="fa-solid fa-gem"></i><h3>Chất lượng rõ ràng</h3><p>Mô tả, giá và biến thể sản phẩm được trình bày minh bạch trước khi bạn đặt mua.</p></article><article class="value-card"><i class="fa-solid fa-truck-fast"></i><h3>Giao hàng chủ động</h3><p>Theo dõi đơn hàng theo từng trạng thái và nhận hỗ trợ khi có vấn đề phát sinh.</p></article><article class="value-card"><i class="fa-solid fa-heart"></i><h3>Khách hàng là trung tâm</h3><p>Chính sách đổi trả trong 7 ngày và đội ngũ sẵn sàng lắng nghe mọi phản hồi.</p></article></div></div></section>
      <section class="about-section"><div class="container"><div class="about-cta"><div><h2>Muốn Urban Tribe hỗ trợ bạn?</h2><p>Gửi câu hỏi, góp ý hoặc yêu cầu hỗ trợ qua trang liên hệ.</p></div><a class="btn btn-primary" href="contact.php">Liên hệ ngay <i class="fa-solid fa-arrow-right"></i></a></div></div></section>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
  </body>
</html>
