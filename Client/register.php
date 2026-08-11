<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
if (!empty($_SESSION['client_user_id']))redirect('index.php');
$notice=flash('auth');
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width,initial-scale=1" name="viewport" />
    <title>
        Đăng ký -
        <?= e(STORE_NAME) ?>
    </title>
    <link href="assets/css/store.css?v=20260810" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
</head>

<body>
    <?php include 'includes/header.php';?>
    <main class="auth-page">
        <section class="auth-card" style="width:min(600px,calc(100% - 32px))">
            <h1>Tạo tài khoản</h1>
            <p class="text-muted" style="text-align:center">Đăng ký miễn phí để mua hàng và đánh giá sản phẩm.</p>
            <?php if($notice):?>
            <div class="alert alert-<?= e($notice['type']) ?>">
                <?= e($notice['message']) ?>
            </div>
            <?php endif;?>
            <form action="AuthController.php" method="post">
                <?= csrf_input() ?>
                <input name="action" type="hidden" value="register" />
                <div class="form-grid">
                    <div class="field full">
                        <label>Họ và tên *</label>
                        <input minlength="2" name="full_name" required="" />
                    </div>
                    <div class="field">
                        <label>Tên đăng nhập *</label>
                        <input maxlength="50" minlength="3" name="username" required="" />
                    </div>
                    <div class="field">
                        <label>Email *</label>
                        <input name="email" required="" type="email" />
                    </div>
                    <div class="field">
                        <label>Số điện thoại</label>
                        <input inputmode="numeric" name="phone" pattern="0[0-9]{9}" />
                    </div>
                    <div>
                    </div>
                    <div class="field">
                        <label>Mật khẩu *</label>
                        <input minlength="6" name="password" required="" type="password" />
                    </div>
                    <div class="field">
                        <label>Xác nhận mật khẩu *</label>
                        <input minlength="6" name="password_confirmation" required="" type="password" />
                    </div>
                </div>
                <button class="btn btn-primary" style="width:100%;margin-top:8px">Đăng ký</button>
            </form>
            <p style="text-align:center">
                Đã có tài khoản?
                <a class="text-danger" href="login.php">
                    <strong>Đăng nhập</strong>
                </a>
            </p>
        </section>
    </main>
    <?php include 'includes/footer.php';?>
</body>

</html>
