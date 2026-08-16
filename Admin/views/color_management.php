<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/ColorModel.php';
require_admin('login.php');
$colorModel = new ColorModel($conn);
$colors = $colorModel->getAllColors()->fetch_all(MYSQLI_ASSOC);
$editColorId = (int) ($_GET['edit'] ?? 0);
$editColor = $editColorId > 0 ? $colorModel->getColorById($editColorId) : null;
$pageTitle = 'Quản lý màu sắc';
$pageSubtitle = 'Thuộc tính màu dùng cho biến thể sản phẩm';
$notice = flash('admin');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width,initial-scale=1" name="viewport"/>
    <title>Màu sắc</title>
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
                  <h3>Danh sách màu</h3>
                  <p class="text-muted"><?= count($colors) ?> màu sắc</p>
                </div>
              </div>
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Màu</th>
                      <th>Tên</th>
                      <th>Mã</th>
                      <th>Trạng thái</th>
                      <th>Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($colors as $color):?>
                    <tr>
                      <td>
                        <span class="color-dot" style="background:<?= e($color['color_code']) ?>">
                        </span>
                      </td>
                      <td>
                        <strong><?= e($color['color_name']) ?></strong>
                      </td>
                      <td><?= e($color['color_code']) ?></td>
                      <td>
                        <span class="badge <?= $color['status']==='Hiện'?'active':'inactive' ?>">
                          <?= $color['status']==='Hiện'?'Hiển thị':'Tạm ẩn' ?>
                        </span>
                      </td>
                      <td class="actions">
                        <a class="btn btn-sm btn-secondary" href="?edit=<?= (int)$color['id'] ?>">
                          <i class="fa-solid fa-pen"></i>
                        </a>
                        <form action="../controllers/ColorController.php" method="post" onsubmit="return confirm('Xóa màu này?')">
                          <?= csrf_input() ?>
                          <input name="action" type="hidden" value="delete"/>
                          <input name="id" type="hidden" value="<?= (int)$color['id'] ?>"/>
                          <button class="btn btn-sm btn-danger">
                            <i class="fa-solid fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach;?>
                    <?php if (empty($colors)):?>
                    <tr>
                      <td class="empty" colspan="5">Chưa có màu sắc.</td>
                    </tr>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
            </section>
            <section class="card">
              <h3><?= $editColor?'Cập nhật màu':'Thêm màu' ?></h3>
              <form action="../controllers/ColorController.php" method="post">
                <?= csrf_input() ?>
                <input name="action" type="hidden" value="save"/>
                <input name="id" type="hidden" value="<?= (int)($editColor['id']??0) ?>"/>
                <div class="field">
                  <label>Tên màu *</label>
                  <input name="color_name" required="" value="<?= e($editColor['color_name']??'') ?>"/>
                </div>
                <div class="field" style="margin-top:12px">
                  <label>Mã màu *</label>
                  <input name="color_code" required="" style="height:48px" type="color" value="<?= e($editColor['color_code']??'#111827') ?>"/>
                </div>
                <div class="field" style="margin-top:12px">
                  <label>Trạng thái</label>
                  <select name="status">
                    <option <?= ($editColor['status']??'Hiện')==='Hiện'?'selected':'' ?> value="Hiện">
                      Hiển thị
                    </option>
                    <option <?= ($editColor['status']??'')==='Ẩn'?'selected':'' ?> value="Ẩn">
                      Tạm ẩn
                    </option>
                  </select>
                </div>
                <div class="form-actions" style="margin-top:18px">
                  <button class="btn btn-primary">Lưu màu</button>
                  <?php if($editColor):?>
                  <a class="btn btn-secondary" href="color_management.php">Hủy sửa</a>
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
