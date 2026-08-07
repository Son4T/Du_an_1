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
        Đăng nhập -
        <?= e(STORE_NAME) ?>
    </title>
    <link href="style.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
</head>

<body>
    <?php include 'includes/header.php';?>
    <main class="auth-page">
        <section class="auth-card">
            <h1>Đăng nhập</h1>
            <p class="text-muted" style="text-align:center">Theo dõi đơn hàng và mua sắm nhanh hơn.</p>
            <?php if($notice):?>
            <div class="alert alert-<?= e($notice['type']) ?>">
                <?= e($notice['message']) ?>
            </div>
            <?php endif;?>
            <form action="AuthController.php" method="post">
                <?= csrf_input() ?>
                <input name="action" type="hidden" value="login" />
                <div class="field">
                    <label>Tên đăng nhập hoặc email</label>
                    <input autocomplete="username" name="login" required="" />
                </div>
                <div class="field">
                    <label>Mật khẩu</label>
                    <input autocomplete="current-password" name="password" required="" type="password" />
                </div>
                <button class="btn btn-primary" style="width:100%">Đăng nhập</button>
            </form>
            <p style="text-align:center">
                Chưa có tài khoản?
                <a class="text-danger" href="register.php">
                    <strong>Đăng ký ngay</strong>
                </a>
            </p>
        </section>
    </main>
    <?php include 'includes/footer.php';?>
</body>

</html>