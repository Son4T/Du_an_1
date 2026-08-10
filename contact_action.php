<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/ContactModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

verify_csrf();

$contactData = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'phone' => clean_phone($_POST['phone'] ?? ''),
    'subject' => trim($_POST['subject'] ?? ''),
    'message' => trim($_POST['message'] ?? ''),
];

try {
    if (
        mb_strlen($contactData['full_name']) < 2
        || mb_strlen($contactData['full_name']) > 120
    ) {
        throw new RuntimeException('Họ tên phải từ 2 đến 120 ký tự.');
    }

    if (
        !filter_var($contactData['email'], FILTER_VALIDATE_EMAIL)
        || mb_strlen($contactData['email']) > 120
    ) {
        throw new RuntimeException('Email chưa hợp lệ.');
    }

    $phoneIsInvalid = $contactData['phone'] !== ''
        && !preg_match('/^0\d{9}$/', $contactData['phone']);

    if ($phoneIsInvalid) {
        throw new RuntimeException(
            'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0.'
        );
    }

    if (
        mb_strlen($contactData['subject']) < 3
        || mb_strlen($contactData['subject']) > 200
    ) {
        throw new RuntimeException('Tiêu đề phải từ 3 đến 200 ký tự.');
    }

    if (
        mb_strlen($contactData['message']) < 10
        || mb_strlen($contactData['message']) > 2000
    ) {
        throw new RuntimeException(
            'Nội dung phản hồi phải từ 10 đến 2000 ký tự.'
        );
    }

    $contactModel = new ContactModel($conn);
    $contactModel->create($contactData);

    flash(
        'client',
        'Cảm ơn bạn. Phản hồi đã được gửi đến quản trị viên.'
    );
} catch (Throwable $error) {
    flash('client', public_error_message($error), 'error');
}

redirect('contact.php');
