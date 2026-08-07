<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/UserModel.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
verify_csrf();
$action = $_POST['action'] ?? '';
if ($action === 'login') {
    $lockUntil = (int) ($_SESSION['admin_login_lock_until'] ?? 0);
    if ($lockUntil > time()) {
        $seconds = $lockUntil - time();
        flash('auth', "Bạn đã nhập sai nhiều lần. Vui lòng thử lại sau {$seconds} giây.", 'error');
        redirect('../views/login.php');
    }
    $login = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($login === '' || $password === '') {
        flash('auth', 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.', 'error');
        redirect('../views/login.php');
    }
    $user = (new UserModel($conn))->findForLogin($login);
    $valid = $user && $user['role'] === 'admin' && $user['status'] === 'active' && password_verify($password, $user['password_hash']);
    if (!$valid) {
        $attempts = (int) ($_SESSION['admin_login_attempts'] ?? 0) + 1;
        $_SESSION['admin_login_attempts'] = $attempts;
        if ($attempts >= 5) {
            $_SESSION['admin_login_lock_until'] = time() + 60;
            $_SESSION['admin_login_attempts'] = 0;
        }
        flash('auth', 'Tài khoản quản trị hoặc mật khẩu không chính xác.', 'error');
        redirect('../views/login.php');
    }
    unset($_SESSION['admin_login_attempts'], $_SESSION['admin_login_lock_until']);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_name'] = $user['full_name'] ?: $user['username'];
    $_SESSION['admin_role'] = 'admin';
    redirect('../views/dashboard.php');
}
if ($action === 'logout') {
    unset( $_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name'], $_SESSION['admin_role'] );
    session_regenerate_id(true);
    redirect('../views/login.php');
}
http_response_code(400);
echo 'Hành động không hợp lệ.';
