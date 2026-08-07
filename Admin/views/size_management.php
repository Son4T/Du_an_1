<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/SizeModel.php';
require_admin('login.php');
$sizeModel = new SizeModel($conn);
$sizes = $sizeModel->getAllSizes()->fetch_all(MYSQLI_ASSOC);
$editSizeId = (int) ($_GET['edit'] ?? 0);
$editSize = $editSizeId > 0 ? $sizeModel->getSizeById($editSizeId) : null;
$pageTitle = 'Quản lý kích thước';
$pageSubtitle = 'Thuộc tính size dùng cho biến thể sản phẩm';
$notice = flash('admin');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>Kích thước</title>
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
                  <h3>Danh sách kích thước</h3>
                  <p class="text-muted"><?= count($sizes) ?> kích thước</p>
                </div>
              </div>
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Mã</th>
                      <th>Tên kích thước</th>
                      <th>Trạng thái</th>
                      <th>Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($sizes as $size):?>
                    <tr>
                      <td>
                        <strong><?= e($size['size_code']) ?></strong>
                      </td>
                      <td><?= e($size['size_name']) ?></td>
                      <td>
                        <span class="badge <?= $size['status']==='Hiện'?'active':'inactive' ?>">
                          <?= $size['status']==='Hiện'?'Hiển thị':'Tạm ẩn' ?>
                        </span>
                      </td>
                      <td class="actions">
                        <a class="btn btn-sm btn-secondary" href="?edit=<?= (int)$size['id'] ?>">
                          <i class="fa-solid fa-pen"></i>
                        </a>
                        <form action="../controllers/SizeController.php" method="post" onsubmit="return confirm('Xóa kích thước này?')">
                          <?= csrf_input() ?>
                          <input name="action" type="hidden" value="delete"/>
                          <input name="id" type="hidden" value="<?= (int)$size['id'] ?>"/>
                          <button class="btn btn-sm btn-danger">
                            <i class="fa-solid fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach;?>
                    <?php if (empty($sizes)):?>
                    <tr>
                      <td class="empty" colspan="4">Chưa có kích thước.</td>
                    </tr>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
            </section>
            <section class="card">
              <h3><?= $editSize?'Cập nhật kích thước':'Thêm kích thước' ?></h3>
              <form action="../controllers/SizeController.php" method="post">
                <?= csrf_input() ?>
                <input name="action" type="hidden" value="save"/>
                <input name="id" type="hidden" value="<?= (int)($editSize['id']??0) ?>"/>
                <div class="field">
                  <label>Mã size *</label>
                  <input maxlength="20" name="size_code" placeholder="S, M, L, XL..." required="" value="<?= e($editSize['size_code']??'') ?>"/>
                </div>
                <div class="field" style="margin-top:12px">
                  <label>Tên hiển thị *</label>
                  <input name="size_name" placeholder="Size M" required="" value="<?= e($editSize['size_name']??'') ?>"/>
                </div>
                <div class="field" style="margin-top:12px">
                  <label>Trạng thái</label>
                  <select name="status">
                    <option <?= ($editSize['status']??'Hiện')==='Hiện'?'selected':'' ?> value="Hiện">
                      Hiển thị
                    </option>
                    <option <?= ($editSize['status']??'')==='Ẩn'?'selected':'' ?> value="Ẩn">
                      Tạm ẩn
                    </option>
                  </select>
                </div>
                <div class="form-actions" style="margin-top:18px">
                  <button class="btn btn-primary">Lưu kích thước</button>
                  <?php if($editSize):?>
                  <a class="btn btn-secondary" href="size_management.php">Hủy sửa</a>
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
