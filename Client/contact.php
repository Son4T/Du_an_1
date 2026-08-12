<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
$notice=flash('client');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>
      Liên hệ -
      <?= e(STORE_NAME) ?>
    </title>
    <link href="style.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  </head>
  <body>
    <?php include 'includes/header.php';?>
    <section class="page-hero">
      <div class="container">
        <h1>Liên hệ với chúng tôi</h1>
        <p>Gửi câu hỏi, góp ý hoặc yêu cầu hỗ trợ đơn hàng.</p>
      </div>
    </section>
    <main class="section alt">
      <div class="container product-detail">
        <section class="card">
          <h2>Thông tin cửa hàng</h2>
          <p class="detail-desc">Đội ngũ Urban Tribe sẵn sàng hỗ trợ trong thời gian làm việc từ 8:00 đến 21:00 hằng ngày.</p>
          <p>
            <i class="fa-solid fa-location-dot text-danger"></i>
            <?= e(STORE_ADDRESS) ?>
          </p>
          <p>
            <i class="fa-solid fa-phone text-danger"></i>
            <?= e(STORE_PHONE) ?>
          </p>
          <p>
            <i class="fa-solid fa-envelope text-danger"></i>
            <?= e(STORE_EMAIL) ?>
          </p>
          <div style="height:220px;border-radius:14px;background:linear-gradient(135deg,#111827,#475569);display:grid;place-items:center;color:#fff;margin-top:20px">
            <div style="text-align:center">
              <i class="fa-solid fa-map-location-dot" style="font-size:45px"></i>
              <p>Hà Nội, Việt Nam</p>
            </div>
          </div>
        </section>
        <section class="card">
          <h2>Gửi lời nhắn</h2>
          <?php if($notice):?>
          <div class="alert alert-<?= e($notice['type']) ?>">
            <?= e($notice['message']) ?>
          </div>
          <?php endif;?>
          <form action="contact_action.php" method="post">
            <?= csrf_input() ?>
            <div class="form-grid">
              <div class="field">
                <label>Họ và tên *</label>
                <input minlength="2" name="full_name" required="" value="<?= e($_SESSION['client_name']??'') ?>"/>
              </div>
              <div class="field">
                <label>Số điện thoại</label>
                <input name="phone" pattern="0[0-9]{9}"/>
              </div>
              <div class="field full">
                <label>Email *</label>
                <input name="email" required="" type="email"/>
              </div>
              <div class="field full">
                <label>Chủ đề *</label>
                <input minlength="3" name="subject" required=""/>
              </div>
              <div class="field full">
                <label>Nội dung *</label>
                <textarea maxlength="2000" minlength="10" name="message" required="" rows="7"></textarea>
              </div>
            </div>
            <button class="btn btn-primary" style="width:100%">Gửi phản hồi</button>
          </form>
        </section>
      </div>
    </main>
    <?php include 'includes/footer.php';?>
  </body>
</html>
