<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/SizeModel.php';

require_admin('../views/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$sizeModel = new SizeModel($conn);
$action = $_POST['action'] ?? '';

try {
    if ($action === 'save') {
        $sizeId = (int) ($_POST['id'] ?? 0);
        $sizeCode = strtoupper(trim($_POST['size_code'] ?? ''));
        $sizeName = trim($_POST['size_name'] ?? '');
        $status = in_array($_POST['status'] ?? 'Hiện', ['Hiện', 'Ẩn'], true)
            ? $_POST['status']
            : 'Hiện';

        if ($sizeCode === '' || $sizeName === '') {
            throw new RuntimeException(
                'Vui lòng nhập đầy đủ mã và tên kích thước.'
            );
        }

        if ($sizeId > 0) {
            $saved = $sizeModel->updateSize(
                $sizeId,
                $sizeCode,
                $sizeName,
                $status
            );
        } else {
            $saved = $sizeModel->createSize($sizeCode, $sizeName, $status);
        }

        if (!$saved) {
            throw new RuntimeException('Không thể lưu kích thước.');
        }

        flash(
            'admin',
            $sizeId > 0
                ? 'Cập nhật kích thước thành công.'
                : 'Thêm kích thước thành công.'
        );
    } elseif ($action === 'delete') {
        $sizeId = (int) ($_POST['id'] ?? 0);

        if (!$sizeModel->deleteSize($sizeId)) {
            throw new RuntimeException(
                'Kích thước đang được dùng trong biến thể, không thể xóa.'
            );
        }

        flash('admin', 'Xóa kích thước thành công.');
    } else {
        throw new RuntimeException('Hành động không hợp lệ.');
    }
} catch (Throwable $error) {
    flash('admin', $error->getMessage(), 'error');
}

redirect('../views/size_management.php');
