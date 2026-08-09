<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/CommentModel.php';

require_admin('../views/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$commentModel = new CommentModel($conn);
$action = $_POST['action'] ?? '';
$commentId = (int) ($_POST['id'] ?? 0);

try {
    if ($action === 'status') {
        $newStatus = $_POST['status'] ?? '';
        $allowedStatuses = ['pending', 'approved', 'hidden'];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new RuntimeException('Trạng thái không hợp lệ.');
        }

        $commentModel->status($commentId, $newStatus);
        flash('admin', 'Cập nhật bình luận thành công.');
    } elseif ($action === 'delete') {
        $commentModel->delete($commentId);
        flash('admin', 'Xóa bình luận thành công.');
    } else {
        throw new RuntimeException('Hành động không hợp lệ.');
    }
} catch (Throwable $error) {
    flash('admin', $error->getMessage(), 'error');
}

redirect('../views/comment_management.php');
