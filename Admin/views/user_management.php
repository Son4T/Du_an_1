<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/UserModel.php';
require_admin('login.php');
$userModel = new UserModel($conn);

$filters = [
    'keyword' => trim($_GET['keyword'] ?? ''),
    'role' => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? '',
];

$users = $userModel->all($filters);
$editId = (int) ($_GET['edit'] ?? 0);
$editUser = $editId > 0 ? $userModel->find($editId) : null;

$pageTitle = 'Quản lý tài khoản';
$pageSubtitle = 'Khách hàng, quản trị viên và trạng thái truy cập';
$notice = flash('admin');
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width,initial-scale=1" name="viewport" />
    <title>Tài khoản</title>
    <link href="../assets/css/style.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
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
                        <form class="filters compact" method="get">
                            <input name="keyword" placeholder="Tên, email, điện thoại..."
                                value="<?= e($filters['keyword']) ?>" />
                            <select name="role">
                                <option value="">Tất cả quyền</option>
                                <option <?= $filters['role']==='user'?'selected':'' ?> value="user">
                                    Khách hàng
                                </option>
                                <option <?= $filters['role']==='admin'?'selected':'' ?> value="admin">
                                    Quản trị
                                </option>
                            </select>
                            <select name="status">
                                <option value="">Tất cả trạng thái</option>
                                <option <?= $filters['status']==='active'?'selected':'' ?> value="active">
                                    Hoạt động
                                </option>
                                <option <?= $filters['status']==='locked'?'selected':'' ?> value="locked">
                                    Đã khóa
                                </option>
                            </select>
                            <button class="btn btn-secondary">Lọc</button>
                        </form>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tài khoản</th>
                                        <th>Liên hệ</th>
                                        <th>Quyền</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $u):?>
                                    <tr>
                                        <td>
                                            <strong><?= e($u['full_name']?:$u['username']) ?></strong>
                                            <br />
                                            <small class="text-muted">
                                                @
                                                <?= e($u['username']) ?>
                                                ·
                                                <?= date('d/m/Y',strtotime($u['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?= e($u['email']) ?>
                                            <br />
                                            <small class="text-muted"><?= e($u['phone']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $u['role']==='admin'?'confirmed':'active' ?>">
                                                <?= $u['role']==='admin'?'Admin':'Khách hàng' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= e($u['status']) ?>">
                                                <?= $u['status']==='active'?'Hoạt động':'Đã khóa' ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <a class="btn btn-sm btn-secondary" href="?edit=<?= (int)$u['id'] ?>">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form action="../controllers/UserController.php" method="post">
                                                <?= csrf_input() ?>
                                                <input name="action" type="hidden" value="toggle" />
                                                <input name="id" type="hidden" value="<?= (int)$u['id'] ?>" />
                                                <button
                                                    class="btn btn-sm <?= $u['status']==='active'?'btn-warning':'btn-success' ?>"
                                                    title="Khóa/Mở">
                                                    <i
                                                        class="fa-solid <?= $u['status']==='active'?'fa-lock':'fa-lock-open' ?>">
                                                    </i>
                                                </button>
                                            </form>
                                            <form action="../controllers/UserController.php" method="post"
                                                onsubmit="return confirm('Xóa tài khoản này?')">
                                                <?= csrf_input() ?>
                                                <input name="action" type="hidden" value="delete" />
                                                <input name="id" type="hidden" value="<?= (int)$u['id'] ?>" />
                                                <button class="btn btn-sm btn-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                    <?php if(!$users):?>
                                    <tr>
                                        <td class="empty" colspan="5">Không có tài khoản phù hợp.</td>
                                    </tr>
                                    <?php endif;?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <section class="card">
                        <h3><?= $editUser?'Cập nhật tài khoản':'Thêm tài khoản' ?></h3>
                        <form action="../controllers/UserController.php" method="post">
                            <?= csrf_input() ?>
                            <input name="action" type="hidden" value="save" />
                            <input name="id" type="hidden" value="<?= (int)($editUser['id']??0) ?>" />
                            <div class="field">
                                <label>Họ tên</label>
                                <input name="full_name" value="<?= e($editUser['full_name']??'') ?>" />
                            </div>
                            <div class="form-grid" style="margin-top:12px">
                                <div class="field">
                                    <label>Tên đăng nhập *</label>
                                    <input minlength="3" name="username" required=""
                                        value="<?= e($editUser['username']??'') ?>" />
                                </div>
                                <div class="field">
                                    <label>Email *</label>
                                    <input name="email" required="" type="email"
                                        value="<?= e($editUser['email']??'') ?>" />
                                </div>
                                <div class="field">
                                    <label>Số điện thoại</label>
                                    <input name="phone" value="<?= e($editUser['phone']??'') ?>" />
                                </div>
                                <div class="field">
                                    <label>
                                        Mật khẩu
                                        <?= $editUser?'(để trống nếu giữ nguyên)':'*' ?>
                                    </label>
                                    <input <?= $editUser?'':'required minlength="6"' ?> name="password"
                                        type="password" />
                                </div>
                                <div class="field">
                                    <label>Quyền</label>
                                    <select name="role">
                                        <option <?= ($editUser['role']??'user')==='user'?'selected':'' ?> value="user">
                                            Khách hàng
                                        </option>
                                        <option <?= ($editUser['role']??'')==='admin'?'selected':'' ?> value="admin">
                                            Quản trị
                                        </option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Trạng thái</label>
                                    <select name="status">
                                        <option <?= ($editUser['status']??'active')==='active'?'selected':'' ?>
                                            value="active">
                                            Hoạt động
                                        </option>
                                        <option <?= ($editUser['status']??'')==='locked'?'selected':'' ?>
                                            value="locked">
                                            Khóa
                                        </option>
                                    </select>
                                </div>
                                <div class="field full">
                                    <label>Địa chỉ</label>
                                    <textarea name="address" rows="3"><?= e($editUser['address']??'') ?></textarea>
                                </div>
                            </div>
                            <div class="form-actions" style="margin-top:18px">
                                <button class="btn btn-primary">Lưu tài khoản</button>
                                <?php if ($editUser): ?>
                                <a class="btn btn-secondary" href="user_management.php">Hủy sửa</a>
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