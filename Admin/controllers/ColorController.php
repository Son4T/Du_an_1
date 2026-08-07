<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/ColorModel.php';

require_admin('../views/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$colorModel = new ColorModel($conn);
$action = $_POST['action'] ?? '';

try {
    if ($action === 'save') {
        $colorId = (int) ($_POST['id'] ?? 0);
        $colorCode = trim($_POST['color_code'] ?? '');
        $colorName = trim($_POST['color_name'] ?? '');
        $status = in_array($_POST['status'] ?? 'Hiện', ['Hiện', 'Ẩn'], true)
            ? $_POST['status']
            : 'Hiện';

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorCode)) {
            throw new RuntimeException('Mã màu phải có dạng #RRGGBB.');
        }

        if (mb_strlen($colorName) < 2) {
            throw new RuntimeException('Tên màu không hợp lệ.');
        }

        if ($colorId > 0) {
            $saved = $colorModel->updateColor(
                $colorId,
                $colorCode,
                $colorName,
                $status
            );
        } else {
            $saved = $colorModel->createColor($colorCode, $colorName, $status);
        }

        if (!$saved) {
            throw new RuntimeException('Không thể lưu màu sắc.');
        }

        flash(
            'admin',
            $colorId > 0 ? 'Cập nhật màu thành công.' : 'Thêm màu thành công.'
        );
    } elseif ($action === 'delete') {
        $colorId = (int) ($_POST['id'] ?? 0);

        if (!$colorModel->deleteColor($colorId)) {
            throw new RuntimeException(
                'Màu đang được dùng trong biến thể, không thể xóa.'
            );
        }

        flash('admin', 'Xóa màu thành công.');
    } else {
        throw new RuntimeException('Hành động không hợp lệ.');
    }
} catch (Throwable $error) {
    flash('admin', $error->getMessage(), 'error');
}

redirect('../views/color_management.php');
