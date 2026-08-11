<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/ProductModel.php';
require_once dirname(__DIR__) . '/Admin/models/CommentModel.php';
require_once __DIR__ . '/includes/cart_helpers.php';
$id=(int)($_GET['id']??0);
$pm=new ProductModel($conn);
$product=$pm->getProductWithVariants($id);
if (!$product||$product['status']!=='Hiện') {
    http_response_code(404);
    exit('Sản phẩm không tồn tại hoặc đã ngừng bán.');
}
$pm->increaseViews($id);
$comments=(new CommentModel($conn))->approvedForProduct($id);
$related=$pm->related($id,(int)$product['category_id'],4);
$effective=(float)$product['sale_price']>0?(float)$product['sale_price']:(float)$product['base_price'];
$notice=flash('client');
$available=array_values(array_filter($product['variants'],fn($v)=>(int)$v['stock']>0));
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>
      <?= e($product['name']) ?>
      -
      <?= e(STORE_NAME) ?>
    </title>
    <meta content="<?= e(mb_substr(strip_tags($product['description']),0,155)) ?>" name="description"/>
    <link href="assets/css/store.css?v=20260810" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  </head>
  <body>
    <?php include 'includes/header.php';?>
    <main class="section">
      <div class="container">
        <?php if($notice):?>
        <div class="alert alert-<?= e($notice['type']) ?>">
          <?= e($notice['message']) ?>
        </div>
        <?php endif;?>
        <div class="product-detail">
          <div class="detail-image">
            <img alt="<?= e($product['name']) ?>" src="<?= e(product_image_url($product['image_url'],'../')) ?>"/>
          </div>
          <div>
            <span class="product-category">
              <?= e($product['category_name']??'Urban Tribe') ?>
              · Mã
              <?= e($product['product_code']) ?>
            </span>
            <h1 class="detail-title"><?= e($product['name']) ?></h1>
            <div class="detail-price" id="displayPrice">
              <?= money($effective) ?>
              <?php if((float)$product['sale_price']>0):?>
              <span class="old-price" style="font-size:16px"><?= money($product['base_price']) ?></span>
              <?php endif;?>
            </div>
            <div class="detail-desc">
              <?= nl2br(e($product['description'])) ?>
            </div>
            <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0"/>
            <form action="add_to_cart.php" id="addCartForm" method="post">
              <?= nam_cart_csrf_input() ?>
              <input name="product_id" type="hidden" value="<?= $id ?>"/>
              <h4>Chọn màu và kích thước</h4>
              <div class="variant-grid">
                <?php foreach($product['variants'] as $v):?>
                <label class="variant-option" style="<?= (int)$v['stock']<=0?'opacity:.5':'' ?>">
                  <input
                    <?= (int) $v['stock'] <= 0 ? 'disabled' : '' ?>
                    type="radio"
                    name="variant_id"
                    value="<?= (int) $v['id'] ?>"
                    data-price="<?= (float) $v['price'] ?>"
                    data-stock="<?= (int) $v['stock'] ?>"
                    required
                  >
                  <span class="color-dot" style="background:<?= e($v['color_code']) ?>">
                  </span>
                  <span>
                    <strong><?= e($v['color_name'].' / '.$v['size_name']) ?></strong>
                    <br/>
                    <small>
                      <?= money($v['price']) ?>
                      ·
                      <?= (int)$v['stock']>0?'Còn '.$v['stock']:'Hết hàng' ?>
                    </small>
                  </span>
                </label>
                <?php endforeach;?>
              </div>
              <h4>Số lượng</h4>
              <div class="quantity-control">
                <button id="minus" type="button">−</button>
                <input id="qty" max="1" min="1" name="quantity" type="number" value="1"/>
                <button id="plus" type="button">+</button>
              </div>
              <small class="text-muted" id="stockHelp">Chọn phân loại để xem tồn kho.</small>
              <div class="detail-actions">
                <button class="btn btn-primary" <?= !$available?'disabled':'' ?>>
                  <i class="fa-solid fa-bag-shopping"></i>
                  <?= $available?'Thêm vào giỏ':'Tạm hết hàng' ?>
                </button>
                <a class="btn btn-outline" href="products.php">Tiếp tục xem</a>
              </div>
            </form>
            <div class="benefit-grid" style="grid-template-columns:repeat(2,1fr);margin-top:28px;border:1px solid #e2e8f0;border-radius:14px">
              <div class="benefit">
                <i class="fa-solid fa-truck"></i>
                <div>
                  <strong>Giao nhanh</strong>
                  <small>Toàn quốc</small>
                </div>
              </div>
              <div class="benefit">
                <i class="fa-solid fa-shield"></i>
                <div>
                  <strong>Đổi trả</strong>
                  <small>Trong 7 ngày</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    <section class="section alt">
      <div class="container review-layout">
        <div>
          <div class="section-head">
            <div>
              <h2>Đánh giá sản phẩm</h2>
              <p>
                <?= count($comments) ?>
                đánh giá đã được duyệt
              </p>
            </div>
          </div>
          <div class="card">
            <?php foreach($comments as $c):?>
            <article class="review">
              <div class="review-head">
                <div>
                  <strong><?= e($c['full_name']?:$c['username']) ?></strong>
                  <div class="stars">
                    <?= str_repeat('★',(int)$c['rating']) ?>
                  </div>
                </div>
                <small class="text-muted"><?= date('d/m/Y',strtotime($c['created_at'])) ?></small>
              </div>
              <p><?= nl2br(e($c['content'])) ?></p>
            </article>
            <?php endforeach;?>
            <?php if(!$comments):?>
            <div class="empty">
              Chưa có đánh giá được duyệt.
            </div>
            <?php endif;?>
          </div>
        </div>
        <aside class="card">
          <h3>Gửi đánh giá</h3>
          <?php if(!empty($_SESSION['client_user_id'])):?>
          <p class="text-muted">Bình luận sẽ hiển thị sau khi quản trị viên duyệt.</p>
          <form action="comment_action.php" method="post">
            <?= csrf_input() ?>
            <input name="product_id" type="hidden" value="<?= $id ?>"/>
            <div class="field">
              <label>Số sao</label>
              <select name="rating" required="">
                <option value="5">5 sao – Rất tốt</option>
                <option value="4">4 sao – Tốt</option>
                <option value="3">3 sao – Bình thường</option>
                <option value="2">2 sao – Chưa tốt</option>
                <option value="1">1 sao – Không hài lòng</option>
              </select>
            </div>
            <div class="field">
              <label>Nội dung</label>
              <textarea maxlength="1000" minlength="10" name="content" required="" rows="5"></textarea>
            </div>
            <button class="btn btn-dark" style="width:100%">Gửi đánh giá</button>
          </form>
          <?php else:?>
          <p class="text-muted">Bạn cần đăng nhập để đánh giá sản phẩm.</p>
          <a class="btn btn-primary" href="login.php">Đăng nhập</a>
          <?php endif;?>
        </aside>
      </div>
    </section>
    <?php if($related):?>
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <h2>Sản phẩm liên quan</h2>
          </div>
        </div>
        <div class="product-grid">
          <?php foreach ($related as $relatedProduct):?>
          <article class="product-card">
            <a href="product_detail.php?id=<?= (int)$relatedProduct['id'] ?>">
              <div class="product-image">
                <img alt="<?= e($relatedProduct['name']) ?>" loading="lazy" src="<?= e(product_image_url($relatedProduct['image_url'],'../')) ?>"/>
              </div>
              <div class="product-info">
                <h3 class="product-name"><?= e($relatedProduct['name']) ?></h3>
                <span class="price"><?= money($relatedProduct['effective_price']) ?></span>
              </div>
            </a>
          </article>
          <?php endforeach;?>
        </div>
      </div>
    </section>
    <?php endif;?>
    <?php include 'includes/footer.php';?>
    <script>
      const variantRadios = document.querySelectorAll('[name=variant_id]');
      const quantityInput = document.getElementById('qty');
      const stockHelp = document.getElementById('stockHelp');
      const displayPrice = document.getElementById('displayPrice');
      const minusButton = document.getElementById('minus');
      const plusButton = document.getElementById('plus');
      const addCartForm = document.getElementById('addCartForm');

      function getSelectedVariant() {
        return document.querySelector('[name=variant_id]:checked');
      }

      variantRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
          quantityInput.max = radio.dataset.stock;
          quantityInput.value = 1;
          stockHelp.textContent = `Tối đa ${radio.dataset.stock} sản phẩm`;
          displayPrice.firstChild.textContent =
            Number(radio.dataset.price).toLocaleString('vi-VN') + ' đ';
        });
      });

      minusButton.addEventListener('click', function () {
        quantityInput.value = Math.max(1, Number(quantityInput.value) - 1);
      });

      plusButton.addEventListener('click', function () {
        const selectedVariant = getSelectedVariant();

        if (selectedVariant) {
          quantityInput.value = Math.min(
            Number(selectedVariant.dataset.stock),
            Number(quantityInput.value) + 1
          );
        }
      });

      addCartForm.addEventListener('submit', function (event) {
        const selectedVariant = getSelectedVariant();
        const quantityIsInvalid = !selectedVariant
          || Number(quantityInput.value) > Number(selectedVariant.dataset.stock);

        if (quantityIsInvalid) {
          event.preventDefault();
          alert('Vui lòng chọn phân loại và số lượng hợp lệ.');
        }
      });
    </script>
  </body>
</html>
