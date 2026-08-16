<?php

if (session_status() === PHP_SESSION_NONE) {
    // Thiết lập session an toàn nhưng vẫn chạy trên localhost HTTP của XAMPP.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once dirname(__DIR__) . '/config/app.php';

/** Escape dữ liệu trước khi hiển thị ra HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Định dạng tiền Việt Nam. */
function money(float|int|string|null $value): string
{
    return number_format((float) $value, 0, ',', '.') . ' đ';
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

/**
 * Lưu hoặc lấy thông báo tạm trong session.
 */
function flash(
    string $key,
    ?string $message = null,
    string $type = 'success'
): ?array {
    if ($message !== null) {
        $_SESSION['_flash'][$key] = [
            'message' => $message,
            'type' => $type,
        ];

        return null;
    }

    $flashMessage = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $flashMessage;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . e(csrf_token())
        . '">';
}

function verify_csrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['_csrf'] ?? '';

    if (!$submittedToken || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(419);
        die(
            'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. '
            . 'Vui lòng tải lại trang.'
        );
    }
}

function require_admin(string $loginPath = 'login.php'): void
{
    $isAdmin = !empty($_SESSION['admin_id'])
        && ($_SESSION['admin_role'] ?? '') === 'admin';

    if (!$isAdmin) {
        flash('auth', 'Vui lòng đăng nhập bằng tài khoản quản trị.', 'error');
        redirect($loginPath);
    }
}

function require_client(string $loginPath = 'login.php'): void
{
    if (empty($_SESSION['client_user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']
            ?? 'index.php';

        flash('auth', 'Vui lòng đăng nhập để tiếp tục.', 'error');
        redirect($loginPath);
    }
}

function admin_name(): string
{
    return $_SESSION['admin_name']
        ?? $_SESSION['admin_username']
        ?? 'Quản trị viên';
}

function order_status_text(string $status): string
{
    $statusNames = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'delivered' => 'Đã giao',
        'success' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];

    return $statusNames[$status] ?? $status;
}

function payment_status_text(string $status): string
{
    $statusNames = [
        'unpaid' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'refunded' => 'Đã hoàn tiền',
    ];

    return $statusNames[$status] ?? $status;
}

function payment_method_text(string $method): string
{
    $methodNames = [
        'cod' => 'Thanh toán khi nhận hàng',
        'zalopay' => 'Cổng thanh toán ZaloPay',
    ];

    return $methodNames[$method] ?? $method;
}

function create_order_code(int $id): string
{
    return 'UT'
        . date('ymd')
        . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
}

function shipping_fee(float $subtotal): float
{
    return $subtotal >= FREE_SHIPPING_FROM ? 0 : SHIPPING_FEE;
}

function product_image_url(?string $path, string $prefix = '../'): string
{
    if (!$path) {
        return $prefix . 'Admin/public/uploads/products/placeholder.svg';
    }

    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    $path = ltrim($path, '/');

    if (str_starts_with($path, 'Admin/')) {
        return $prefix . $path;
    }

    if (str_starts_with($path, 'public/')) {
        return $prefix . 'Admin/' . $path;
    }

    return $prefix
        . 'Admin/public/uploads/products/'
        . basename($path);
}

function slugify(string $text): string
{
    $vietnameseCharacters = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a',
        'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a',
        'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e',
        'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o',
        'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o',
        'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u',
        'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd',
    ];

    $lowerText = mb_strtolower(trim($text), 'UTF-8');
    $textWithoutAccents = strtr($lowerText, $vietnameseCharacters);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $textWithoutAccents) ?? '';

    return trim($slug, '-') ?: 'item-' . time();
}

function clean_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?? '';
}

/**
 * Bind số lượng tham số động cho prepared statement.
 */
function bind_dynamic_params(
    mysqli_stmt $statement,
    string $types,
    array &$params
): void {
    if ($types === '') {
        return;
    }

    $arguments = [$types];

    foreach ($params as &$value) {
        $arguments[] = &$value;
    }

    call_user_func_array([$statement, 'bind_param'], $arguments);
}

/**
 * Kiểm tra database cũ đã có đủ cột ZaloPay hay chưa.
 * Hàm chỉ đọc cấu trúc bảng, không tự ý sửa dữ liệu.
 */
function assert_zalopay_database_ready(mysqli $conn): void
{
    $requiredColumns = [
        'payment_method',
        'zalopay_app_trans_id',
        'zalopay_app_user',
        'zalopay_zp_trans_id',
        'zalopay_order_url',
        'zalopay_order_token',
        'zalopay_qr_code',
        'zalopay_return_code',
        'payment_message',
        'paid_at',
        'payment_updated_at',
    ];

    $statement = $conn->prepare(
        "SELECT COLUMN_NAME, COLUMN_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'orders'"
    );
    $statement->execute();

    $columns = [];

    foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $column) {
        $columns[$column['COLUMN_NAME']] = strtolower($column['COLUMN_TYPE']);
    }

    $missingColumns = array_diff($requiredColumns, array_keys($columns));
    $paymentMethodType = $columns['payment_method'] ?? '';
    $supportsZaloPay = str_contains($paymentMethodType, "'zalopay'");

    if (!empty($missingColumns) || !$supportsZaloPay) {
        throw new RuntimeException(
            'Database chưa được nâng cấp cho ZaloPay. '
            . 'Hãy mở phpMyAdmin, chọn database urban_tribe và import file '
            . 'database_fix_zalopay.sql, sau đó đặt hàng lại.'
        );
    }
}

function public_error_message(
    Throwable $error,
    string $fallback = 'Có lỗi hệ thống xảy ra. Vui lòng thử lại sau.'
): string {
    $isPublicRuntimeError = $error instanceof RuntimeException
        && !($error instanceof mysqli_sql_exception);

    if ($isPublicRuntimeError) {
        return $error->getMessage();
    }

    error_log($error->getMessage());

    return $fallback;
}

function allowed_order_transition(string $from, string $to): bool
{
    $allowedTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping', 'cancelled'],
        'shipping' => ['delivered'],
        'delivered' => ['success'],
        'success' => [],
        'cancelled' => [],
    ];

    return in_array($to, $allowedTransitions[$from] ?? [], true);
}