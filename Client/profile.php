<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/UserModel.php';
require_client('login.php');
$userModel = new UserModel($conn);
$userId = (int) $_SESSION['client_user_id'];
$user = $userModel->find($userId);
if (!$user) {
    unset($_SESSION['client_user_id']);
    redirect('login.php');
}
$notice = flash('client');
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width,initial-scale=1" name="viewport" />
    <title>Hồ sơ cá nhân</title>
    <link href="style.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
</head>

<body>
    <?php include 'includes/header.php';?>
    <section class="page-hero">
        <div class="container">
            <h1>Hồ sơ cá nhân</h1>
            <p>Cập nhật thông tin nhận hàng và bảo mật tài khoản.</p>
        </div>
    </section>
    <main class="section alt">
        <div class="container">
            <?php if($notice):?>
            <div class="alert alert-<?= e($notice['type']) ?>">
                <?= e($notice['message']) ?>
            </div>
            <?php endif;?>
            <div class="profile-layout">
                <section class="card">
                    <h2>Thông tin tài khoản</h2>
                    <form action="AuthController.php" method="post">
                        <?= csrf_input() ?>
                        <input name="action" type="hidden" value="profile" />
                        <div class="field">
                            <label>Tên đăng nhập</label>
                            <input disabled="" value="<?= e($user['username']) ?>" />
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input disabled="" value="<?= e($user['email']) ?>" />
                        </div>
                        <div class="field">
                            <label>Họ và tên *</label>
                            <input name="full_name" required="" value="<?= e($user['full_name']) ?>" />
                        </div>
                        <div class="field">
                            <label>Số điện thoại</label>
                            <input name="phone" pattern="0[0-9]{9}" value="<?= e($user['phone']) ?>" />
                        </div>
                        <div class="field">
                            <label>Địa chỉ mặc định</label>
                            <textarea name="address" rows="4"><?= e($user['address']) ?></textarea>
                        </div>
                        <button class="btn btn-primary">Lưu thông tin</button>
                    </form>
                </section>
                <section class="card">
                    <h2>Đổi mật khẩu</h2>
                    <form action="AuthController.php" method="post">
                        <?= csrf_input() ?>
                        <input name="action" type="hidden" value="change_password" />
                        <div class="field">
                            <label>Mật khẩu hiện tại</label>
                            <input name="current_password" required="" type="password" />
                        </div>
                        <div class="field">
                            <label>Mật khẩu mới</label>
                            <input minlength="6" name="new_password" required="" type="password" />
                        </div>
                        <div class="field">
                            <label>Xác nhận mật khẩu mới</label>
                            <input minlength="6" name="new_password_confirmation" required="" type="password" />
                        </div>
                        <button class="btn btn-dark">Đổi mật khẩu</button>
                    </form>
                    <hr style="border:0;border-top:1px solid #e2e8f0;margin:25px 0" />
                    <a class="btn btn-outline" href="order_history.php">
                        <i class="fa-solid fa-box"></i>
                        Xem đơn hàng của tôi
                    </a>
                </section>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php';?>
</body>

</html>