<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
if (!empty($_SESSION['admin_id'])) redirect('dashboard.php');
$notice=flash('auth');
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width,initial-scale=1" name="viewport" />
    <title>
        Đăng nhập quản trị -
        <?= e(STORE_NAME) ?>
    </title>
    <link href="../assets/css/style.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
</head>

<body class="login-page">
    <main class="login-card">
        <div class="login-logo">
            UT
        </div>
        <h1>Đăng nhập Admin</h1>
        <p>
            Hệ thống quản trị
            <?= e(STORE_NAME) ?>
        </p>
        <?php if($notice): ?>
        <div class="alert alert-<?= e($notice['type']) ?>">
            <?= e($notice['message']) ?>
        </div>
        <?php endif; ?>
        <form action="../controllers/AuthController.php" method="post">
            <?= csrf_input() ?>
            <input name="action" type="hidden" value="login" />
            <div class="field">
                <label>Tên đăng nhập hoặc email</label>
                <input autocomplete="username" name="username" required="" />
            </div>
            <div class="field" style="margin-top:14px">
                <label>Mật khẩu</label>
                <input autocomplete="current-password" name="password" required="" type="password" />
            </div>
            <button class="btn btn-primary" style="width:100%;margin-top:20px">
                <i class="fa-solid fa-right-to-bracket"></i>
                Đăng nhập
            </button>
        </form>
    </main>
</body>

</html>