<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="logo" href="index.php" style="color: #fff">
                    <span class="logo-mark">UT</span>
                    <div>URBAN TRIBE</div>
                </a>

                <p>Thời trang trẻ trung, dễ mặc và phù hợp phong cách hằng ngày.</p>

                <p>
                    <i class="fa-solid fa-location-dot"></i>
                    <?= e(STORE_ADDRESS) ?>
                    <br>

                    <i class="fa-solid fa-phone"></i>
                    <?= e(STORE_PHONE) ?>
                    <br>

                    <i class="fa-solid fa-envelope"></i>
                    <?= e(STORE_EMAIL) ?>
                </p>
            </div>

            <div>
                <h4>Mua sắm</h4>
                <a href="products.php">Tất cả sản phẩm</a>
                <a href="products.php?sort=new">Sản phẩm mới</a>
                <a href="products.php?in_stock=1">Còn hàng</a>
                <a href="cart.php">Giỏ hàng</a>
            </div>

            <div>
                <h4>Hỗ trợ</h4>
                <a href="about.php">Giới thiệu</a>
                <a href="contact.php">Liên hệ</a>
                <a href="order_history.php">Tra cứu đơn</a>
                <a href="#">Chính sách đổi trả</a>
            </div>

            <div>
                <h4>Tài khoản</h4>
                <a href="login.php">Đăng nhập</a>
                <a href="register.php">Đăng ký</a>
                <a href="profile.php">Hồ sơ cá nhân</a>
                <a href="../Admin/index.php">Quản trị</a>
            </div>
        </div>

        <div class="copyright">
            © <?= date('Y') ?> <?= e(STORE_NAME) ?>.
            Phong cách của riêng bạn.
        </div>
    </div>
</footer>