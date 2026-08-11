<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/UserModel.php';

require_admin('../views/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$userModel = new UserModel($conn);
$action = $_POST['action'] ?? '';
$adminId = (int) $_SESSION['admin_id'];

try {
    if ($action === 'save') {
        // Bước 1: Lấy dữ liệu tài khoản từ form.
        $userId = (int) ($_POST['id'] ?? 0);

        $userData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'full_name' => trim($_POST['full_name'] ?? ''),
            'phone' => clean_phone($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'role' => in_array(
                $_POST['role'] ?? 'user',
                ['admin', 'user'],
                true
            ) ? $_POST['role'] : 'user',
            'status' => in_array(
                $_POST['status'] ?? 'active',
                ['active', 'locked'],
                true
            ) ? $_POST['status'] : 'active',
        ];

        // Bước 2: Kiểm tra dữ liệu hợp lệ.
        if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $userData['username'])) {
            throw new RuntimeException(
                'Tên đăng nhập dài 3–50 ký tự và chỉ gồm chữ, số, '
                . 'dấu chấm hoặc gạch dưới.'
            );
        }

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email chưa hợp lệ.');
        }

        $phoneIsInvalid = $userData['phone'] !== ''
            && !preg_match('/^0\d{9}$/', $userData['phone']);

        if ($phoneIsInvalid) {
            throw new RuntimeException(
                'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.'
            );
        }

        $mustCheckPassword = $userId === 0 || $userData['password'] !== '';

        if ($mustCheckPassword && strlen($userData['password']) < 6) {
            throw new RuntimeException('Mật khẩu phải có ít nhất 6 ký tự.');
        }

        // Bước 3: Gọi Model để thêm hoặc cập nhật tài khoản.
        if ($userId > 0) {
            $saved = $userModel->update($userId, $userData, $adminId);
        } else {
            $saved = $userModel->create($userData);
        }

        if (!$saved) {
            throw new RuntimeException(
                'Không thể lưu tài khoản. Không được tự hạ quyền/khóa '
                . 'tài khoản hoặc dữ liệu đã bị trùng.'
            );
        }

        flash(
            'admin',
            $userId > 0
                ? 'Cập nhật tài khoản thành công.'
                : 'Thêm tài khoản thành công.'
        );
    } elseif ($action === 'toggle') {
        $userId = (int) ($_POST['id'] ?? 0);

        if (!$userModel->toggle($userId, $adminId)) {
            throw new RuntimeException(
                'Không thể khóa tài khoản quản trị đang đăng nhập.'
            );
        }

        flash('admin', 'Đã cập nhật trạng thái tài khoản.');
    } elseif ($action === 'delete') {
        $userId = (int) ($_POST['id'] ?? 0);

        if (!$userModel->delete($userId, $adminId)) {
            throw new RuntimeException(
                'Không thể xóa chính bạn, quản trị viên cuối cùng hoặc '
                . 'tài khoản đã có đơn hàng. Hãy khóa tài khoản thay vì xóa.'
            );
        }

        flash('admin', 'Xóa tài khoản thành công.');
    } else {
        throw new RuntimeException('Hành động không hợp lệ.');
    }
} catch (Throwable $error) {
    flash('admin', $error->getMessage(), 'error');
}

redirect('../views/user_management.php');