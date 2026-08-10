<?php
require_once dirname(__DIR__) . '/Admin/config/bootstrap.php';
require_once dirname(__DIR__) . '/Admin/models/CommentModel.php';
require_once dirname(__DIR__) . '/Admin/models/ProductModel.php';
require_client('login.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
verify_csrf();
$productId = (int) ($_POST['product_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$content = trim($_POST['content'] ?? '');
try {
    $product = (new ProductModel($conn))->getProductById($productId, true);
    if (!$product) {
        throw new RuntimeException('Sản phẩm không tồn tại hoặc đã ngừng bán.');
    }
    if ($rating < 1 || $rating > 5) {
        throw new RuntimeException('Vui lòng chọn mức đánh giá từ 1 đến 5 sao.');
    }
    if (mb_strlen($content) < 10 || mb_strlen($content) > 1000) {
        throw new RuntimeException('Nội dung đánh giá phải từ 10 đến 1000 ký tự.');
    }
    (new CommentModel($conn))->create( (int) $_SESSION['client_user_id'], $productId, $content, $rating );
    flash('client', 'Cảm ơn bạn. Đánh giá đang chờ quản trị viên duyệt.');
} catch (Throwable $e) {
    flash('client', public_error_message($e), 'error');
}
redirect('product_detail.php?id=' . $productId . '#reviews');
