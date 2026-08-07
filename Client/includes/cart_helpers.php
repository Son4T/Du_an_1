<?php
/**
 * Các hàm chỉ phục vụ luồng giỏ hàng và đơn hàng (phần Nam).
 * Giỏ hàng được lưu theo variant_id trong session để luôn kiểm tra tồn kho thực tế.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/Admin/config/app.php';

function nam_cart_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function nam_cart_money(float|int|string $value): string
{
    return number_format((float) $value, 0, ',', '.') . ' đ';
}

function nam_cart_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function nam_cart_csrf_token(): string
{
    if (empty($_SESSION['_nam_cart_csrf'])) {
        $_SESSION['_nam_cart_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_nam_cart_csrf'];
}

function nam_cart_csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . nam_cart_e(nam_cart_csrf_token()) . '">';
}

function nam_cart_verify_csrf(): void
{
    if (!hash_equals(
        $_SESSION['_nam_cart_csrf'] ?? '',
        $_POST['csrf_token'] ?? ''
    )) {
        http_response_code(419);
        exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
    }
}

function nam_cart_flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['_nam_cart_flash'] = compact('message', 'type');
        return null;
    }

    $notice = $_SESSION['_nam_cart_flash'] ?? null;
    unset($_SESSION['_nam_cart_flash']);
    return $notice;
}

function nam_cart_items(mysqli $conn): array
{
    $rawCart = $_SESSION['cart'] ?? [];
    $cart = [];

    foreach ($rawCart as $variantId => $quantity) {
        $variantId = (int) $variantId;
        $quantity = (int) $quantity;
        if ($variantId > 0 && $quantity > 0) {
            $cart[$variantId] = min($quantity, 99);
        }
    }

    $_SESSION['cart'] = $cart;
    if (!$cart) {
        return [];
    }

    // Các ID đều đã được ép kiểu int ở trên nên an toàn để tạo danh sách IN.
    $variantIds = implode(',', array_keys($cart));
    $sql = "SELECT pv.id AS variant_id, pv.product_id, pv.sku, pv.stock,
                   pv.price AS variant_price, p.name AS product_name,
                   p.image_url, p.status AS product_status,
                   c.color_name, s.size_name,
                   COALESCE(NULLIF(p.sale_price, 0), NULLIF(pv.price, 0), p.base_price) AS unit_price
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            INNER JOIN colors c ON c.id = pv.color_id
            INNER JOIN sizes s ON s.id = pv.size_id
            WHERE pv.id IN ($variantIds)";
    $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    $byVariant = [];
    foreach ($rows as $row) {
        $byVariant[(int) $row['variant_id']] = $row;
    }

    $items = [];
    foreach ($cart as $variantId => $quantity) {
        if (!isset($byVariant[$variantId])) {
            unset($_SESSION['cart'][$variantId]);
            continue;
        }

        $item = $byVariant[$variantId];
        $item['quantity'] = $quantity;
        $item['variant_label'] = $item['color_name'] . ' · ' . $item['size_name'];
        $item['line_total'] = (float) $item['unit_price'] * $quantity;
        $items[] = $item;
    }

    return $items;
}

function nam_cart_summary(array $items): array
{
    $subtotal = 0.0;
    $count = 0;
    foreach ($items as $item) {
        $subtotal += (float) $item['line_total'];
        $count += (int) $item['quantity'];
    }

    $shipping = $subtotal > 0 && $subtotal < FREE_SHIPPING_FROM
        ? (float) SHIPPING_FEE
        : 0.0;

    return [
        'count' => $count,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $subtotal + $shipping,
    ];
}

function nam_cart_image(string $path): string
{
    if ($path === '') {
        return '../Admin/public/uploads/products/placeholder.svg';
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return '../Admin/' . ltrim($path, '/');
}

function nam_cart_current_user_id(): ?int
{
    $userId = (int) ($_SESSION['client_user_id'] ?? 0);
    return $userId > 0 ? $userId : null;
}

function nam_cart_order_status_text(string $status): string
{
    return [
        'pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao', 'delivered' => 'Đã giao',
        'success' => 'Hoàn thành', 'cancelled' => 'Đã hủy',
    ][$status] ?? $status;
}
