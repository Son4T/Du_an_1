<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/models/ContactModel.php';

require_admin('../views/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$contactModel = new ContactModel($conn);
$action = $_POST['action'] ?? '';
$contactId = (int) ($_POST['id'] ?? 0);

try {
    if ($action === 'status') {
        $newStatus = $_POST['status'] ?? '';
        $allowedStatuses = ['new', 'processing', 'done'];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new RuntimeException('Trạng thái không hợp lệ.');
        }

        $contactModel->status($contactId, $newStatus);
        flash('admin', 'Cập nhật phản hồi thành công.');
    } elseif ($action === 'delete') {
        $contactModel->delete($contactId);
        flash('admin', 'Xóa phản hồi thành công.');
    } else {
        throw new RuntimeException('Hành động không hợp lệ.');
    }
} catch (Throwable $error) {
    flash('admin', $error->getMessage(), 'error');
}

redirect('../views/contact_management.php');
