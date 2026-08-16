<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/ProductModel.php';
require_once dirname(__DIR__) . '/Admin/models/CategoryModel.php';
$filters = [
    'keyword' => trim($_GET['keyword'] ?? ''),
    'category_id' => (int) ($_GET['category_id'] ?? 0),
    'price' => $_GET['price'] ?? '',
    'sort' => $_GET['sort'] ?? 'new',
    'in_stock' => !empty($_GET['in_stock']),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$catalogResult = (new ProductModel($conn))->catalog($filters, $page, 12);
$products = $catalogResult['items'];
$categories = (new CategoryModel($conn))->all(true);
function page_url(int $pageNumber): string
{
    $queryParams = $_GET;
    $queryParams['page'] = $pageNumber;

    return '?' . http_build_query($queryParams);
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>
      Sản phẩm -
      <?= e(STORE_NAME) ?>
    </title>
    <link href="assets/css/store.css?v=20260810" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  </head>
  <body>
    <?php include 'includes/header.php';?>
    <section class="page-hero">
      <div class="container">
        <h1>Sản phẩm</h1>
        <p>Tìm kiếm và lựa chọn sản phẩm phù hợp với bạn.</p>
      </div>
    </section>
    <main class="section">
      <div class="container shop-layout">
        <aside>
          <form class="filter-card" method="get">
            <h3 style="margin-top:0">Bộ lọc</h3>
            <div class="field">
              <label>Từ khóa</label>
              <input name="keyword" placeholder="Tên hoặc mã sản phẩm" value="<?= e($filters['keyword']) ?>"/>
            </div>
            <div class="field">
              <label>Danh mục</label>
              <select name="category_id">
                <option value="0">Tất cả danh mục</option>
                <?php foreach ($categories as $category):?>
                <option <?= $filters['category_id']==$category['id']?'selected':'' ?> value="<?= (int)$category['id'] ?>">
                  <?= e($category['name']) ?>
                </option>
                <?php endforeach;?>
              </select>
            </div>
            <div class="filter-group">
              <h4>Khoảng giá</h4>
              <label class="radio-row">
                <input <?= $filters['price']===''?'checked':'' ?> name="price" type="radio" value=""/>
                Tất cả
              </label>
              <label class="radio-row">
                <input <?= $filters['price']==='1'?'checked':'' ?> name="price" type="radio" value="1"/>
                Dưới 200.000đ
              </label>
              <label class="radio-row">
                <input <?= $filters['price']==='2'?'checked':'' ?> name="price" type="radio" value="2"/>
                200.000đ – 500.000đ
              </label>
              <label class="radio-row">
                <input <?= $filters['price']==='3'?'checked':'' ?> name="price" type="radio" value="3"/>
                Trên 500.000đ
              </label>
            </div>
            <label class="check-row">
              <input <?= $filters['in_stock']?'checked':'' ?> name="in_stock" type="checkbox" value="1"/>
              Chỉ sản phẩm còn hàng
            </label>
            <div class="field">
              <label>Sắp xếp</label>
              <select name="sort">
                <option <?= $filters['sort']==='new'?'selected':'' ?> value="new">
                  Mới nhất
                </option>
                <option <?= $filters['sort']==='price_asc'?'selected':'' ?> value="price_asc">
                  Giá tăng dần
                </option>
                <option <?= $filters['sort']==='price_desc'?'selected':'' ?> value="price_desc">
                  Giá giảm dần
                </option>
                <option <?= $filters['sort']==='popular'?'selected':'' ?> value="popular">
                  Xem nhiều
                </option>
              </select>
            </div>
            <button class="btn btn-dark" style="width:100%">
              <i class="fa-solid fa-filter"></i>
              Áp dụng
            </button>
            <a class="btn btn-outline" href="products.php" style="width:100%;margin-top:8px">Xóa bộ lọc</a>
          </form>
        </aside>
        <section>
          <div class="shop-toolbar">
            <div>
              <strong>
                <?= (int)$catalogResult['total'] ?>
                sản phẩm
              </strong>
              <br/>
              <small class="text-muted">
                Trang
                <?= $page ?>
                /
                <?= (int)$catalogResult['pages'] ?>
              </small>
            </div>
          </div>
          <div class="product-grid">
            <?php foreach ($products as $product):?>
            <article class="product-card">
              <?php if((float)$product['sale_price']>0):?>
              <span class="sale-badge">SALE</span>
              <?php endif;?>
              <a href="product_detail.php?id=<?= (int)$product['id'] ?>">
                <div class="product-image">
                  <img alt="<?= e($product['name']) ?>" loading="lazy" src="<?= e(product_image_url($product['image_url'],'../')) ?>"/>
                </div>
                <div class="product-info">
                  <span class="product-category"><?= e($product['category_name']??'Chưa phân loại') ?></span>
                  <h3 class="product-name"><?= e($product['name']) ?></h3>
                  <?php if($product['avg_rating']):?>
                  <div class="stars">
                    ★
                    <?= number_format((float)$product['avg_rating'],1) ?>
                  </div>
                  <?php endif;?>
                  <span class="price"><?= money($product['effective_price']) ?></span>
                  <?php if((float)$product['sale_price']>0):?>
                  <span class="old-price"><?= money($product['base_price']) ?></span>
                  <?php endif;?>
                  <div class="stock-note <?= (int)$product['total_stock']<=0?'out':'' ?>">
                    <?= (int)$product['total_stock']>0?'Còn '.(int)$product['total_stock'].' sản phẩm':'Tạm hết hàng' ?>
                  </div>
                </div>
              </a>
            </article>
            <?php endforeach;?>
            <?php if(!$products):?>
            <div class="empty" style="grid-column:1/-1">
              Không tìm thấy sản phẩm phù hợp.
            </div>
            <?php endif;?>
          </div>
          <?php if($catalogResult['pages']>1):?>
          <nav class="pagination">
            <?php for ($pageItem = 1; $pageItem <= $catalogResult['pages']; $pageItem++):?>
            <a class="<?= $pageItem === $page?'active':'' ?>" href="<?= e(page_url($pageItem)) ?>">
              <?= $pageItem ?>
            </a>
            <?php endfor;?>
          </nav>
          <?php endif;?>
        </section>
      </div>
    </main>
    <?php include 'includes/footer.php';?>
  </body>
</html>
