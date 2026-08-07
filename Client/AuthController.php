<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$action = $_POST['action'] ?? '';
$userModel = new UserModel($conn);

try {
    if ($action === 'register') {
        $userData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => clean_phone($_POST['phone'] ?? ''),
            'address' => '',
            'role' => 'user',
            'status' => 'active',
        ];

        if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $userData['username'])) {
            throw new RuntimeException(
                'Tên đăng nhập dài 3–50 ký tự và chỉ gồm chữ, số, '
                . 'dấu chấm hoặc gạch dưới.'
            );
        }

        if (mb_strlen($userData['full_name']) < 2) {
            throw new RuntimeException('Vui lòng nhập họ và tên hợp lệ.');
        }

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email không hợp lệ.');
        }

        $phoneIsInvalid = $userData['phone'] !== ''
            && !preg_match('/^0\d{9}$/', $userData['phone']);

        if ($phoneIsInvalid) {
            throw new RuntimeException(
                'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.'
            );
        }

        if (strlen($userData['password']) < 6) {
            throw new RuntimeException('Mật khẩu phải có ít nhất 6 ký tự.');
        }

        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        if ($userData['password'] !== $passwordConfirmation) {
            throw new RuntimeException('Mật khẩu xác nhận không khớp.');
        }

        if (!$userModel->create($userData)) {
            throw new RuntimeException('Tên đăng nhập hoặc email đã tồn tại.');
        }

        flash('auth', 'Đăng ký thành công. Vui lòng đăng nhập.');
        redirect('login.php');
    }

    if ($action === 'login') {
        $lockUntil = (int) ($_SESSION['client_login_lock_until'] ?? 0);

        if ($lockUntil > time()) {
            $remainingSeconds = $lockUntil - time();

            throw new RuntimeException(
                "Bạn đã nhập sai nhiều lần. Vui lòng thử lại sau "
                . "{$remainingSeconds} giây."
            );
        }

        $loginValue = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = $userModel->findForLogin($loginValue);

        $loginIsValid = $user
            && $user['role'] === 'user'
            && $user['status'] === 'active'
            && password_verify($password, $user['password_hash']);

        if (!$loginIsValid) {
            $loginAttempts = (int) (
                $_SESSION['client_login_attempts'] ?? 0
            ) + 1;
            $_SESSION['client_login_attempts'] = $loginAttempts;

            if ($loginAttempts >= 5) {
                $_SESSION['client_login_lock_until'] = time() + 60;
                $_SESSION['client_login_attempts'] = 0;
            }

            throw new RuntimeException(
                'Tài khoản, mật khẩu không đúng hoặc tài khoản đã bị khóa.'
            );
        }

        unset(
            $_SESSION['client_login_attempts'],
            $_SESSION['client_login_lock_until']
        );

        session_regenerate_id(true);
        $_SESSION['client_user_id'] = (int) $user['id'];
        $_SESSION['client_username'] = $user['username'];
        $_SESSION['client_name'] = $user['full_name'] ?: $user['username'];

        $nextPage = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);

        $unsafeRedirect = str_contains($nextPage, '://')
            || str_starts_with($nextPage, '//')
            || preg_match('/[\r\n]/', $nextPage);

        if ($unsafeRedirect) {
            $nextPage = 'index.php';
        }

        redirect($nextPage);
    }

    if ($action === 'logout') {
        unset(
            $_SESSION['client_user_id'],
            $_SESSION['client_username'],
            $_SESSION['client_name']
        );

        session_regenerate_id(true);
        redirect('index.php');
    }

    if ($action === 'profile') {
        require_client('login.php');

        $profileData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => clean_phone($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
        ];

        if (mb_strlen($profileData['full_name']) < 2) {
            throw new RuntimeException('Họ tên không hợp lệ.');
        }

        $phoneIsInvalid = $profileData['phone'] !== ''
            && !preg_match('/^0\d{9}$/', $profileData['phone']);

        if ($phoneIsInvalid) {
            throw new RuntimeException(
                'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.'
            );
        }

        $userId = (int) $_SESSION['client_user_id'];
        $userModel->updateProfile($userId, $profileData);
        $_SESSION['client_name'] = $profileData['full_name'];

        flash('client', 'Cập nhật hồ sơ thành công.');
        redirect('profile.php');
    }

    if ($action === 'change_password') {
        require_client('login.php');

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirmation = $_POST['new_password_confirmation'] ?? '';

        $newPasswordIsInvalid = strlen($newPassword) < 6
            || $newPassword !== $newPasswordConfirmation;

        if ($newPasswordIsInvalid) {
            throw new RuntimeException(
                'Mật khẩu mới phải từ 6 ký tự và xác nhận phải khớp.'
            );
        }

        $userId = (int) $_SESSION['client_user_id'];
        $changed = $userModel->changePassword(
            $userId,
            $currentPassword,
            $newPassword
        );

        if (!$changed) {
            throw new RuntimeException('Mật khẩu hiện tại không chính xác.');
        }

        flash('client', 'Đổi mật khẩu thành công.');
        redirect('profile.php');
    }

    throw new RuntimeException('Hành động không hợp lệ.');
} catch (Throwable $error) {
    $flashKey = in_array($action, ['register', 'login'], true)
        ? 'auth'
        : 'client';

    flash($flashKey, public_error_message($error), 'error');

    $errorPage = match ($action) {
        'register' => 'register.php',
        'login' => 'login.php',
        default => 'profile.php',
    };

    redirect($errorPage);
}