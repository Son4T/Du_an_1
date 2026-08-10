<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/ProductModel.php';
require_once dirname(__DIR__) . '/Admin/models/CategoryModel.php';
$products=(new ProductModel($conn))->featured(8);
$categories=array_slice((new CategoryModel($conn))->all(true),0,4);
$notice=flash('client');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>
      <?= e(STORE_NAME) ?>
      - Thời trang phong cách
    </title>
    <meta content="Urban Tribe - cửa hàng thời trang trẻ trung, sản phẩm đa dạng và mua sắm thuận tiện." name="description"/>
    <link href="assets/css/store.css?v=20260810" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  </head>
  <body>
    <?php include 'includes/header.php';?>
    <?php if($notice):?>
    <div class="container" style="margin-top:16px">
      <div class="alert alert-<?= e($notice['type']) ?>">
        <?= e($notice['message']) ?>
      </div>
    </div>
    <?php endif;?>
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <span class="eyebrow">Bộ sưu tập mới</span>
          <h1>Phong cách của riêng bạn.</h1>
          <p>Khám phá các thiết kế trẻ trung, dễ phối và phù hợp với nhịp sống hiện đại. Chọn màu, kích thước và đặt hàng ngay trên website.</p>
          <div class="hero-actions">
            <a class="btn btn-primary" href="products.php">
              Mua sắm ngay
              <i class="fa-solid fa-arrow-right"></i>
            </a>
            <a class="btn btn-outline" href="about.php">Về Urban Tribe</a>
          </div>
        </div>
      </div>
    </section>
    <section class="benefits">
      <div class="container benefit-grid">
        <div class="benefit">
          <i class="fa-solid fa-truck-fast"></i>
          <div>
            <strong>Giao hàng toàn quốc</strong>
            <small>Theo dõi trạng thái đơn</small>
          </div>
        </div>
        <div class="benefit">
          <i class="fa-solid fa-rotate-left"></i>
          <div>
            <strong>Đổi trả trong 7 ngày</strong>
            <small>Hỗ trợ sản phẩm lỗi</small>
          </div>
        </div>
        <div class="benefit">
          <i class="fa-solid fa-shield-halved"></i>
          <div>
            <strong>Thanh toán an toàn</strong>
            <small>COD hoặc ZaloPay Sandbox</small>
          </div>
        </div>
        <div class="benefit">
          <i class="fa-solid fa-headset"></i>
          <div>
            <strong>Hỗ trợ khách hàng</strong>
            <small>Tư vấn nhanh chóng</small>
          </div>
        </div>
      </div>
    </section>
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <h2>Danh mục nổi bật</h2>
            <p>Lựa chọn sản phẩm theo phong cách bạn yêu thích.</p>
          </div>
          <a href="products.php">
            Xem tất cả
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
        <div class="category-grid">
          <?php foreach($categories as $c):?>
          <a class="category-card" href="products.php?category_id=<?= (int)$c['id'] ?>">
            <strong><?= e($c['name']) ?></strong>
            <small>
              <?= (int)$c['product_count'] ?>
              sản phẩm · Khám phá ngay
            </small>
          </a>
          <?php endforeach;?>
          <?php if(!$categories):?>
          <div class="empty">
            Hãy thêm danh mục trong trang quản trị.
          </div>
          <?php endif;?>
        </div>
      </div>
    </section>
    <section class="section alt">
      <div class="container">
        <div class="section-head">
          <div>
            <h2>Sản phẩm mới & nổi bật</h2>
            <p>Các sản phẩm được cập nhật gần đây.</p>
          </div>
          <a href="products.php">
            Tất cả sản phẩm
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
        <div class="product-grid">
          <?php foreach($products as $p):?>
          <article class="product-card">
            <?php if((float)$p['sale_price']>0):?>
            <span class="sale-badge">SALE</span>
            <?php endif;?>
            <a href="product_detail.php?id=<?= (int)$p['id'] ?>">
              <div class="product-image">
                <img alt="<?= e($p['name']) ?>" loading="lazy" src="<?= e(product_image_url($p['image_url'],'../')) ?>"/>
              </div>
              <div class="product-info">
                <span class="product-category"><?= e($p['category_name']??'Urban Tribe') ?></span>
                <h3 class="product-name"><?= e($p['name']) ?></h3>
                <span class="price"><?= money($p['effective_price']) ?></span>
                <?php if((float)$p['sale_price']>0):?>
                <span class="old-price"><?= money($p['base_price']) ?></span>
                <?php endif;?>
                <div class="stock-note <?= (int)$p['total_stock']<=0?'out':'' ?>">
                  <?= (int)$p['total_stock']>0?'Còn hàng':'Tạm hết hàng' ?>
                </div>
              </div>
            </a>
          </article>
          <?php endforeach;?>
          <?php if(!$products):?>
          <div class="empty">
            Chưa có sản phẩm. Vào Admin để thêm dữ liệu.
          </div>
          <?php endif;?>
        </div>
      </div>
    </section>
    <section class="section">
      <div class="container">
        <div class="card" style="background:#111827;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px;padding:36px">
          <div>
            <span class="eyebrow">URBAN TRIBE MEMBERS</span>
            <h2 style="font-size:32px;margin:10px 0">Đăng ký để theo dõi đơn hàng</h2>
            <p style="color:#cbd5e1">Lưu thông tin nhận hàng, xem lịch sử mua sắm và gửi đánh giá sản phẩm.</p>
          </div>
          <a class="btn btn-primary" href="register.php">Tạo tài khoản</a>
        </div>
      </div>
    </section>
    <?php include 'includes/footer.php';?>
  </body>
</html>
