<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/ContactModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('contact.php');
}

verify_csrf();

$contact = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'phone' => clean_phone($_POST['phone'] ?? ''),
    'subject' => trim($_POST['subject'] ?? ''),
    'message' => trim($_POST['message'] ?? ''),
];

try {
    if (mb_strlen($contact['full_name']) < 2) {
        throw new RuntimeException('Vui lòng nhập họ và tên hợp lệ.');
    }
    if (!filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Email chưa đúng định dạng.');
    }
    if ($contact['phone'] !== '' && (strlen($contact['phone']) < 9 || strlen($contact['phone']) > 15)) {
        throw new RuntimeException('Số điện thoại chưa hợp lệ.');
    }
    if (mb_strlen($contact['subject']) < 3 || mb_strlen($contact['message']) < 10) {
        throw new RuntimeException('Vui lòng nhập chủ đề và nội dung chi tiết hơn.');
    }
    if (!(new ContactModel($conn))->create($contact)) {
        throw new RuntimeException('Không thể gửi liên hệ. Vui lòng thử lại.');
    }
    flash('client', 'Cảm ơn bạn! Urban Tribe đã nhận được tin nhắn và sẽ phản hồi sớm.', 'success');
} catch (Throwable $error) {
    flash('client', $error instanceof RuntimeException ? $error->getMessage() : 'Không thể gửi liên hệ. Vui lòng thử lại.', 'error');
}

redirect('contact.php');
