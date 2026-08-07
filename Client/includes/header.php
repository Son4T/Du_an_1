<?php
require_once dirname(__DIR__, 2) . '/Admin/config/bootstrap.php';

$isLoggedIn = !empty($_SESSION['client_user_id']);
$clientName = $_SESSION['client_name']
    ?? $_SESSION['client_username']
    ?? 'Tài khoản';

$cartCount = 0;

foreach ($_SESSION['cart'] ?? [] as $cartItem) {
    $cartCount += (int) ($cartItem['quantity'] ?? 0);
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="announcement">
    Miễn phí vận chuyển cho đơn từ <?= money(FREE_SHIPPING_FROM) ?>
    · Đổi trả trong 7 ngày
</div>

<header class="main-header">
    <div class="container header-inner">
        <a class="logo" href="index.php">
            <span class="logo-mark">UT</span>
            <div>URBAN TRIBE</div>
        </a>

        <button class="header-icon mobile-toggle" id="mobileToggle" type="button">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="main-nav" id="mainNav">
            <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                Trang chủ
            </a>

            <a class="<?= in_array(
            $currentPage,
            ['products.php', 'product_detail.php'],
            true
        ) ? 'active' : '' ?>" href="products.php">
                Sản phẩm
            </a>

            <a class="<?= $currentPage === 'about.php' ? 'active' : '' ?>" href="about.php">
                Giới thiệu
            </a>

            <a class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="contact.php">
                Liên hệ
            </a>
        </nav>

        <form class="header-search" method="get" action="products.php">
            <input name="keyword" placeholder="Tìm sản phẩm...">
            <button aria-label="Tìm kiếm">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <div class="header-actions">
            <a class="header-icon" href="cart.php" title="Giỏ hàng">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="cart-count" id="cartCount"><?= $cartCount ?></span>
            </a>

            <?php if ($isLoggedIn): ?>
            <div class="user-dropdown">
                <button class="header-icon" title="<?= e($clientName) ?>" type="button">
                    <i class="fa-solid fa-user"></i>
                </button>

                <div class="dropdown-menu">
                    <div style="padding: 9px 10px">
                        <strong><?= e($clientName) ?></strong>
                    </div>

                    <a href="profile.php">
                        <i class="fa-solid fa-address-card"></i>
                        Hồ sơ
                    </a>

                    <a href="order_history.php">
                        <i class="fa-solid fa-box"></i>
                        Đơn hàng
                    </a>

                    <form method="post" action="AuthController.php">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="logout">
                        <button>
                            <i class="fa-solid fa-right-from-bracket"></i>
                            Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <a class="header-icon" href="login.php" title="Đăng nhập">
                <i class="fa-solid fa-user"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
const mobileToggle = document.getElementById('mobileToggle');
const mainNav = document.getElementById('mainNav');

mobileToggle?.addEventListener('click', function() {
    mainNav.classList.toggle('mobile-open');
});
</script>