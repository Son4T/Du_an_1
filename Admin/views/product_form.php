<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/ProductModel.php';
require_once dirname(__DIR__) . '/models/CategoryModel.php';
require_once dirname(__DIR__) . '/models/ColorModel.php';
require_once dirname(__DIR__) . '/models/SizeModel.php';
require_admin('login.php');
$id=(int)($_GET['id']??0);
$product=$id?(new ProductModel($conn))->getProductWithVariants($id):null;
if ($id&&!$product) {
    flash('admin','Không tìm thấy sản phẩm.','error');
    redirect('product_management.php');
}
$categories=(new CategoryModel($conn))->all($id===0);
$colors=(new ColorModel($conn))->getAllColors(true)->fetch_all(MYSQLI_ASSOC);
$sizes=(new SizeModel($conn))->getAllSizes(true)->fetch_all(MYSQLI_ASSOC);
$pageTitle=$id?'Cập nhật sản phẩm':'Thêm sản phẩm';
$pageSubtitle='Quản lý thông tin và từng biến thể màu – kích thước';
$notice=flash('admin');
$variants=$product['variants']??[];
if (!$variants)$variants=[['color_id'=>'','size_id'=>'','sku'=>'','price'=>$product['base_price']??'','stock'=>0]];
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title><?= e($pageTitle) ?></title>
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
          <form action="../controllers/ProductController.php" enctype="multipart/form-data" id="productForm" method="post">
            <?= csrf_input() ?>
            <input name="action" type="hidden" value="save"/>
            <input name="id" type="hidden" value="<?= $id ?>"/>
            <input name="existing_image_url" type="hidden" value="<?= e($product['image_url']??'') ?>"/>
            <div class="page-head">
              <div>
                <h2><?= e($pageTitle) ?></h2>
                <p>Những trường có dấu * là bắt buộc</p>
              </div>
              <div class="actions">
                <a class="btn btn-secondary" href="product_management.php">Quay lại</a>
                <button class="btn btn-primary">
                  <i class="fa-solid fa-floppy-disk"></i>
                  Lưu sản phẩm
                </button>
              </div>
            </div>
            <div class="grid-2">
              <section class="card">
                <h3>Thông tin cơ bản</h3>
                <div class="form-grid">
                  <div class="field">
                    <label>Mã sản phẩm *</label>
                    <input maxlength="30" name="product_code" placeholder="VD: UT-AO-001" required="" value="<?= e($product['product_code']??'') ?>"/>
                  </div>
                  <div class="field">
                    <label>Danh mục *</label>
                    <select name="category_id" required="">
                      <option value="">-- Chọn danh mục --</option>
                      <?php foreach($categories as $c):?>
                      <option <?= (int)($product['category_id']??0)===(int)$c['id']?'selected':'' ?> value="<?= (int)$c['id'] ?>">
                        <?= e($c['name']) ?>
                      </option>
                      <?php endforeach;?>
                    </select>
                  </div>
                  <div class="field full">
                    <label>Tên sản phẩm *</label>
                    <input maxlength="180" minlength="3" name="product_name" required="" value="<?= e($product['name']??'') ?>"/>
                  </div>
                  <div class="field full">
                    <label>Slug</label>
                    <input name="slug" placeholder="Tự động tạo theo tên nếu để trống" value="<?= e($product['slug']??'') ?>"/>
                  </div>
                  <div class="field">
                    <label>Giá gốc *</label>
                    <input min="1000" name="base_price" required="" step="1000" type="number" value="<?= e($product['base_price']??'') ?>"/>
                  </div>
                  <div class="field">
                    <label>Giá khuyến mãi</label>
                    <input min="0" name="sale_price" step="1000" type="number" value="<?= e($product['sale_price']??0) ?>"/>
                  </div>
                  <div class="field">
                    <label>Trạng thái</label>
                    <select name="status">
                      <option <?= ($product['status']??'Hiện')==='Hiện'?'selected':'' ?> value="Hiện">
                        Đang bán
                      </option>
                      <option <?= ($product['status']??'')==='Ẩn'?'selected':'' ?> value="Ẩn">
                        Ngừng bán
                      </option>
                    </select>
                  </div>
                  <label class="check-row" style="align-self:end;padding-bottom:10px">
                    <input <?= !empty($product['featured'])?'checked':'' ?> name="featured" type="checkbox"/>
                    Sản phẩm nổi bật
                  </label>
                  <div class="field full">
                    <label>Mô tả chi tiết</label>
                    <textarea maxlength="5000" name="description" rows="7"><?= e($product['description']??'') ?></textarea>
                  </div>
                </div>
              </section>
              <section class="card">
                <h3>Hình ảnh đại diện</h3>
                <?php if(!empty($product['image_url'])):?>
                <img
                  src="<?= e(product_image_url($product['image_url'], '../../')) ?>"
                  alt="Ảnh sản phẩm"
                  style="
                    width: 180px;
                    height: 220px;
                    object-fit: cover;
                    border-radius: 12px;
                    margin-bottom: 12px;
                  "
                >
                <?php endif;?>
                <div class="field">
                  <label>Chọn ảnh JPG/PNG/WEBP</label>
                  <input accept="image/jpeg,image/png,image/webp" <?= $id?'':'required' ?> name="product_image" type="file"/>
                  <small class="text-muted">Tối đa 5MB. Ảnh dọc tỉ lệ 4:5 cho hiển thị đẹp nhất.</small>
                </div>
                <div class="alert alert-success" style="margin-top:18px">
                  <strong>Lưu ý tồn kho</strong>
                  <br/>
                  Giá và tồn kho thực tế được quản lý theo từng biến thể bên dưới.
                </div>
              </section>
            </div>
            <section class="card" style="margin-top:20px">
              <div class="toolbar">
                <div>
                  <h3>Biến thể sản phẩm</h3>
                  <p class="text-muted">Mỗi cặp màu – kích thước chỉ nên xuất hiện một lần.</p>
                </div>
                <button class="btn btn-success" id="addVariant" type="button">
                  <i class="fa-solid fa-plus"></i>
                  Thêm biến thể
                </button>
              </div>
              <div class="table-wrap">
                <table class="data-table variant-table" id="variantTable">
                  <thead>
                    <tr>
                      <th>Màu *</th>
                      <th>Kích thước *</th>
                      <th>SKU</th>
                      <th>Giá bán *</th>
                      <th>Tồn kho *</th>
                      <th>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($variants as $v):?>
                    <tr>
                      <td>
                        <select name="variant_color_id[]" required="">
                          <option value="">Chọn màu</option>
                          <?php foreach($colors as $c):?>
                          <option <?= (int)$v['color_id']===(int)$c['id']?'selected':'' ?> value="<?= (int)$c['id'] ?>">
                            <?= e($c['color_name']) ?>
                          </option>
                          <?php endforeach;?>
                        </select>
                      </td>
                      <td>
                        <select name="variant_size_id[]" required="">
                          <option value="">Chọn size</option>
                          <?php foreach($sizes as $s):?>
                          <option <?= (int)$v['size_id']===(int)$s['id']?'selected':'' ?> value="<?= (int)$s['id'] ?>">
                            <?= e($s['size_name']) ?>
                          </option>
                          <?php endforeach;?>
                        </select>
                      </td>
                      <td>
                        <input maxlength="60" name="variant_sku[]" placeholder="Tự tạo nếu trống" value="<?= e($v['sku']??'') ?>"/>
                      </td>
                      <td>
                        <input min="1000" name="variant_price[]" required="" step="1000" type="number" value="<?= e($v['price']??($product['base_price']??'')) ?>"/>
                      </td>
                      <td>
                        <input min="0" name="variant_stock[]" required="" type="number" value="<?= (int)($v['stock']??0) ?>"/>
                      </td>
                      <td>
                        <button class="btn btn-sm btn-danger removeVariant" type="button">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                    <?php endforeach;?>
                  </tbody>
                </table>
              </div>
            </section>
            <div class="form-actions" style="margin-top:20px;justify-content:flex-end">
              <a class="btn btn-secondary" href="product_management.php">Hủy</a>
              <button class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Lưu sản phẩm
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
    <template id="variantTemplate">
      <tr>
        <td>
          <select name="variant_color_id[]" required="">
            <option value="">Chọn màu</option>
            <?php foreach($colors as $c):?>
            <option value="<?= (int)$c['id'] ?>">
              <?= e($c['color_name']) ?>
            </option>
            <?php endforeach;?>
          </select>
        </td>
        <td>
          <select name="variant_size_id[]" required="">
            <option value="">Chọn size</option>
            <?php foreach($sizes as $s):?>
            <option value="<?= (int)$s['id'] ?>">
              <?= e($s['size_name']) ?>
            </option>
            <?php endforeach;?>
          </select>
        </td>
        <td>
          <input maxlength="60" name="variant_sku[]" placeholder="Tự tạo nếu trống"/>
        </td>
        <td>
          <input min="1000" name="variant_price[]" required="" step="1000" type="number"/>
        </td>
        <td>
          <input min="0" name="variant_stock[]" required="" type="number" value="0"/>
        </td>
        <td>
          <button class="btn btn-sm btn-danger removeVariant" type="button">
            <i class="fa-solid fa-trash"></i>
          </button>
        </td>
      </tr>
    </template>
    <script>
      const variantTableBody = document.querySelector('#variantTable tbody');
      const addVariantButton = document.getElementById('addVariant');
      const variantTemplate = document.getElementById('variantTemplate');
      const productForm = document.getElementById('productForm');

      addVariantButton.addEventListener('click', function () {
        const newVariantRow = variantTemplate.content.cloneNode(true);
        variantTableBody.append(newVariantRow);
      });

      document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.removeVariant');

        if (!removeButton) {
          return;
        }

        if (variantTableBody.rows.length <= 1) {
          alert('Sản phẩm cần ít nhất một biến thể.');
          return;
        }

        removeButton.closest('tr').remove();
      });

      productForm.addEventListener('submit', function (event) {
        const basePrice = Number(
          document.querySelector('[name=base_price]').value
        );
        const salePrice = Number(
          document.querySelector('[name=sale_price]').value || 0
        );

        if (salePrice > 0 && salePrice >= basePrice) {
          event.preventDefault();
          alert('Giá khuyến mãi phải nhỏ hơn giá gốc.');
        }
      });
    </script>
  </body>
</html>
