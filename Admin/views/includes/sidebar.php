<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$menuGroups = [
    [
        'label' => 'Tổng quan',
        'icon' => 'fa-gauge-high',
        'items' => [
            ['dashboard.php', 'Dashboard'],
        ],
    ],
    [
        'label' => 'Sản phẩm',
        'icon' => 'fa-box-open',
        'items' => [
            ['category_management.php', 'Danh mục'],
            ['product_management.php', 'Sản phẩm'],
            ['color_management.php', 'Màu sắc'],
            ['size_management.php', 'Kích thước'],
        ],
    ],
    [
        'label' => 'Bán hàng',
        'icon' => 'fa-bag-shopping',
        'items' => [
            ['admin_orders.php', 'Đơn hàng'],
            ['comment_management.php', 'Bình luận'],
            ['contact_management.php', 'Phản hồi'],
        ],
    ],
    [
        'label' => 'Hệ thống',
        'icon' => 'fa-gear',
        'items' => [
            ['user_management.php', 'Tài khoản'],
            ['admin_stats.php', 'Thống kê'],
        ],
    ],
];

/**
 * Kiểm tra một menu có đang tương ứng với trang hiện tại hay không.
 */
function is_admin_menu_active(string $menuFile, string $currentPage): bool
{
    if ($currentPage === $menuFile) {
        return true;
    }

    $detailPages = [
        'product_form.php' => 'product_management.php',
        'product_detail.php' => 'product_management.php',
        'user_form.php' => 'user_management.php',
        'user_detail.php' => 'user_management.php',
        'order_detail.php' => 'admin_orders.php',
    ];

    return isset($detailPages[$currentPage])
        && $detailPages[$currentPage] === $menuFile;
}
?>
<aside class="sidebar">
    <a class="sidebar-brand" href="dashboard.php">
        <span>UT</span>
        <div>
            URBAN TRIBE
            <small>ADMIN PANEL</small>
        </div>
    </a>

    <nav class="sidebar-nav">
        <?php foreach ($menuGroups as $menuGroup): ?>
        <?php
      $groupIsActive = false;

      foreach ($menuGroup['items'] as $menuItem) {
          if (is_admin_menu_active($menuItem[0], $currentPage)) {
              $groupIsActive = true;
              break;
          }
      }
      ?>

        <div class="nav-group <?= $groupIsActive ? 'open' : '' ?>">
            <button class="nav-group-title" type="button">
                <i class="fa-solid <?= e($menuGroup['icon']) ?>"></i>
                <span><?= e($menuGroup['label']) ?></span>
                <i class="fa-solid fa-chevron-down arrow"></i>
            </button>

            <div class="nav-submenu">
                <?php foreach ($menuGroup['items'] as $menuItem): ?>
                <?php
            $itemIsActive = is_admin_menu_active(
                $menuItem[0],
                $currentPage
            );
            ?>

                <a class="<?= $itemIsActive ? 'active' : '' ?>" href="<?= e($menuItem[0]) ?>">
                    <?= e($menuItem[1]) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </nav>

    <a class="view-store" href="../../Client/index.php" target="_blank">
        <i class="fa-solid fa-arrow-up-right-from-square"></i>
        Xem cửa hàng
    </a>
</aside>

<script>
const menuButtons = document.querySelectorAll('.nav-group-title');

menuButtons.forEach(function(button) {
    button.addEventListener('click', function() {
        button.parentElement.classList.toggle('open');
    });
});
</script>