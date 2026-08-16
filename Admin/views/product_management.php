<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/ProductModel.php';
require_once dirname(__DIR__) . '/models/CategoryModel.php';
require_admin('login.php');
$filters=['keyword'=>trim($_GET['keyword']??''),'category_id'=>(int)($_GET['category_id']??0),'status'=>$_GET['status']??''];
$products=(new ProductModel($conn))->getAllProducts($filters);
$categories=(new CategoryModel($conn))->all();
$pageTitle='Quản lý sản phẩm';
$pageSubtitle='Sản phẩm, biến thể, giá bán và tồn kho';
$notice=flash('admin');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>Sản phẩm</title>
    <link href="../assets/css/style.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  </head>
  <body>
    <div class="admin-container">
      <?php include 'includes/sidebar.php';?>
      <main class="main-content">
        <?php include 'includes/header.php';?>
        <div class="page">
          <?php if($notice):?>
          <div class="alert alert-<?= e($notice['type']) ?>">
            <?= e($notice['message']) ?>
          </div>
          <?php endif;?>
          <div class="page-head">
            <div>
              <h2>Danh sách sản phẩm</h2>
              <p>
                <?= count($products) ?>
                kết quả
              </p>
            </div>
            <a class="btn btn-primary" href="product_form.php">
              <i class="fa-solid fa-plus"></i>
              Thêm sản phẩm
            </a>
          </div>
          <section class="card">
            <form class="filters compact" method="get">
              <input name="keyword" placeholder="Tìm theo tên hoặc mã..." value="<?= e($filters['keyword']) ?>"/>
              <select name="category_id">
                <option value="0">Tất cả danh mục</option>
                <?php foreach($categories as $c):?>
                <option <?= $filters['category_id']==$c['id']?'selected':'' ?> value="<?= (int)$c['id'] ?>">
                  <?= e($c['name']) ?>
                </option>
                <?php endforeach;?>
              </select>
              <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option <?= $filters['status']==='Hiện'?'selected':'' ?> value="Hiện">
                  Đang bán
                </option>
                <option <?= $filters['status']==='Ẩn'?'selected':'' ?> value="Ẩn">
                  Ngừng bán
                </option>
              </select>
              <button class="btn btn-secondary">
                <i class="fa-solid fa-filter"></i>
                Lọc
              </button>
            </form>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Ảnh</th>
                    <th>Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Biến thể</th>
                    <th>Tồn kho</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($products as $p):?>
                  <tr>
                    <td>
                      <img alt="" class="product-thumb" src="<?= e(product_image_url($p['image_url'],'../../')) ?>"/>
                    </td>
                    <td>
                      <strong><?= e($p['name']) ?></strong>
                      <br/>
                      <small class="text-muted"><?= e($p['product_code']) ?></small>
                      <?php if($p['featured']):?>
                      <span class="badge pending">Nổi bật</span>
                      <?php endif;?>
                    </td>
                    <td><?= e($p['category_name']??'Chưa phân loại') ?></td>
                    <td>
                      <?php if((float)$p['sale_price']>0):?>
                      <strong class="text-danger"><?= money($p['sale_price']) ?></strong>
                      <br/>
                      <small class="text-muted">
                        <s>
                          <?= money($p['base_price']) ?>
                        </s>
                      </small>
                      <?php else:?>
                      <?= money($p['base_price']) ?>
                      <?php endif;?>
                    </td>
                    <td><?= (int)$p['variant_count'] ?></td>
                    <td>
                      <strong class="<?= (int)$p['total_stock']<=5?'text-danger':'' ?>">
                        <?= (int)$p['total_stock'] ?>
                      </strong>
                    </td>
                    <td>
                      <span class="badge <?= $p['status']==='Hiện'?'active':'inactive' ?>">
                        <?= $p['status']==='Hiện'?'Đang bán':'Ngừng bán' ?>
                      </span>
                    </td>
                    <td class="actions">
                      <a class="btn btn-sm btn-secondary" href="product_form.php?id=<?= (int)$p['id'] ?>">
                        <i class="fa-solid fa-pen"></i>
                      </a>
                      <form action="../controllers/ProductController.php" method="post" onsubmit="return confirm('Đổi trạng thái sản phẩm?')">
                        <?= csrf_input() ?>
                        <input name="action" type="hidden" value="toggle"/>
                        <input name="id" type="hidden" value="<?= (int)$p['id'] ?>"/>
                        <button class="btn btn-sm <?= $p['status']==='Hiện'?'btn-danger':'btn-success' ?>">
                          <i class="fa-solid <?= $p['status']==='Hiện'?'fa-eye-slash':'fa-eye' ?>">
                          </i>
                        </button>
                      </form>

                      <form
                        action="../controllers/ProductController.php"
                        method="post"
                        onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này? Đơn hàng cũ vẫn được giữ lại.');"
                      >
                        <?= csrf_input() ?>
                        <input name="action" type="hidden" value="delete"/>
                        <input name="id" type="hidden" value="<?= (int)$p['id'] ?>"/>

                        <button
                          class="btn btn-sm btn-danger"
                          title="Xóa sản phẩm"
                          type="submit"
                        >
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach;?>
                  <?php if(!$products):?>
                  <tr>
                    <td class="empty" colspan="8">Không có sản phẩm phù hợp.</td>
                  </tr>
                  <?php endif;?>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </main>
    </div>
  </body>
</html>
