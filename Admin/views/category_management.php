<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/CategoryModel.php';
require_admin('login.php');
$categoryModel = new CategoryModel($conn);
$categories = $categoryModel->all();
$editCategoryId = (int) ($_GET['edit'] ?? 0);
$editCategory = $editCategoryId > 0 ? $categoryModel->find($editCategoryId) : null;
$pageTitle = 'Quản lý danh mục';
$pageSubtitle = 'Thêm, sửa, xóa và sắp xếp nhóm sản phẩm';
$notice = flash('admin');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>Danh mục</title>
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
          <div class="grid-2">
            <section class="card">
              <div class="toolbar">
                <div>
                  <h3>Danh sách danh mục</h3>
                  <p class="text-muted">
                    <?= count($categories) ?>
                    danh mục
                  </p>
                </div>
              </div>
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Tên</th>
                      <th>Slug</th>
                      <th>Sản phẩm</th>
                      <th>Trạng thái</th>
                      <th>Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($categories as $category):?>
                    <tr>
                      <td>
                        <strong><?= e($category['name']) ?></strong>
                        <br/>
                        <small class="text-muted"><?= e($category['description']) ?></small>
                      </td>
                      <td><?= e($category['slug']) ?></td>
                      <td><?= (int)$category['product_count'] ?></td>
                      <td>
                        <span class="badge <?= $category['status']==='active'?'active':'inactive' ?>">
                          <?= $category['status']==='active'?'Hoạt động':'Tạm ẩn' ?>
                        </span>
                      </td>
                      <td class="actions">
                        <a class="btn btn-sm btn-secondary" href="?edit=<?= (int)$category['id'] ?>">
                          <i class="fa-solid fa-pen"></i>
                        </a>
                        <form action="../controllers/CategoryController.php" method="post" onsubmit="return confirm('Xóa danh mục này?')">
                          <?= csrf_input() ?>
                          <input name="action" type="hidden" value="delete"/>
                          <input name="id" type="hidden" value="<?= (int)$category['id'] ?>"/>
                          <button class="btn btn-sm btn-danger">
                            <i class="fa-solid fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach;?>
                    <?php if(!$categories):?>
                    <tr>
                      <td class="empty" colspan="5">Chưa có danh mục.</td>
                    </tr>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
            </section>
            <section class="card">
              <h3><?= $editCategory?'Cập nhật danh mục':'Thêm danh mục' ?></h3>
              <form action="../controllers/CategoryController.php" method="post">
                <?= csrf_input() ?>
                <input name="action" type="hidden" value="save"/>
                <input name="id" type="hidden" value="<?= (int)($editCategory['id']??0) ?>"/>
                <div class="field">
                  <label>Tên danh mục *</label>
                  <input minlength="2" name="name" required="" value="<?= e($editCategory['name']??'') ?>"/>
                </div>
                <div class="field" style="margin-top:12px">
                  <label>Đường dẫn (slug)</label>
                  <input name="slug" placeholder="Tự tạo nếu để trống" value="<?= e($editCategory['slug']??'') ?>"/>
                </div>
                <div class="field" style="margin-top:12px">
                  <label>Mô tả</label>
                  <textarea name="description" rows="4"><?= e($editCategory['description']??'') ?></textarea>
                </div>
                <div class="form-grid" style="margin-top:12px">
                  <div class="field">
                    <label>Trạng thái</label>
                    <select name="status">
                      <option <?= ($editCategory['status']??'active')==='active'?'selected':'' ?> value="active">
                        Hoạt động
                      </option>
                      <option <?= ($editCategory['status']??'')==='inactive'?'selected':'' ?> value="inactive">
                        Tạm ẩn
                      </option>
                    </select>
                  </div>
                  <div class="field">
                    <label>Thứ tự</label>
                    <input min="0" name="sort_order" type="number" value="<?= (int)($editCategory['sort_order']??0) ?>"/>
                  </div>
                </div>
                <div class="form-actions" style="margin-top:18px">
                  <button class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Lưu danh mục
                  </button>
                  <?php if($editCategory):?>
                  <a class="btn btn-secondary" href="category_management.php">Hủy sửa</a>
                  <?php endif;?>
                </div>
              </form>
            </section>
          </div>
        </div>
      </main>
    </div>
  </body>
</html>
