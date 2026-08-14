<header class="topbar">
    <button class="mobile-menu" type="button" onclick="document.body.classList.toggle('sidebar-open')">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div>
        <h1><?= e($pageTitle ?? 'Bảng điều khiển') ?></h1>
        <p><?= e($pageSubtitle ?? 'Quản lý hoạt động cửa hàng') ?></p>
    </div>

    <div class="topbar-actions">
        <div class="admin-profile">
            <span class="avatar" style="overflow:hidden">
                <?php if (!empty($_SESSION['admin_avatar'])): ?>
                <img src="<?= e(avatar_image_url($_SESSION['admin_avatar'], '../../')) ?>" alt="<?= e(admin_name()) ?>"
                    style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                <?= e(mb_substr(admin_name(), 0, 1)) ?>
                <?php endif; ?>
            </span>

            <div>
                <strong><?= e(admin_name()) ?></strong>
                <small>Quản trị viên</small>
            </div>
        </div>

        <form method="post" action="../controllers/AuthController.php" onsubmit="return confirm('Bạn muốn đăng xuất?')">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="logout">

            <button class="icon-btn danger" title="Đăng xuất">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </form>
    </div>
</header>