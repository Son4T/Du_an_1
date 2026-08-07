<?php

class OrderModel
{
    public function __construct(private mysqli $conn) {}

    public function all(array $filters = []): array
    {
        $where = []; $types = ''; $params = [];
        if (!empty($filters['keyword'])) { $where[] = '(o.order_code LIKE ? OR o.customer_name LIKE ? OR o.phone LIKE ?)'; $keyword = '%' . $filters['keyword'] . '%'; $types .= 'sss'; array_push($params, $keyword, $keyword, $keyword); }
        if (!empty($filters['status']) && in_array($filters['status'], $this->statuses(), true)) { $where[] = 'o.status = ?'; $types .= 's'; $params[] = $filters['status']; }
        $sql = 'SELECT o.*, COUNT(oi.id) AS item_count FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' GROUP BY o.id ORDER BY o.id DESC';
        $statement = $this->conn->prepare($sql);
        if ($params) { $arguments = [$types]; foreach ($params as &$value) { $arguments[] = &$value; } call_user_func_array([$statement, 'bind_param'], $arguments); }
        $statement->execute(); return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function find(int $orderId): ?array
    {
        $statement = $this->conn->prepare('SELECT o.*, u.username FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?');
        $statement->bind_param('i', $orderId); $statement->execute(); $order = $statement->get_result()->fetch_assoc();
        if (!$order) return null;
        $statement = $this->conn->prepare('SELECT * FROM order_items WHERE order_id = ?'); $statement->bind_param('i', $orderId); $statement->execute(); $order['items'] = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        return $order;
    }

    public function updateStatus(int $orderId, string $newStatus): void
    {
        if (!in_array($newStatus, $this->statuses(), true)) throw new RuntimeException('Trạng thái đơn hàng không hợp lệ.');
        $this->conn->begin_transaction();
        try {
            $statement = $this->conn->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE'); $statement->bind_param('i', $orderId); $statement->execute(); $order = $statement->get_result()->fetch_assoc();
            if (!$order) throw new RuntimeException('Không tìm thấy đơn hàng.');
            if (!$this->allowedTransition($order['status'], $newStatus)) throw new RuntimeException('Không thể chuyển trạng thái đơn từ "' . $this->statusText($order['status']) . '".');
            if ($newStatus === 'cancelled') $this->restoreStock($orderId);
            $statement = $this->conn->prepare('UPDATE orders SET status = ? WHERE id = ?'); $statement->bind_param('si', $newStatus, $orderId); $statement->execute();
            $this->conn->commit();
        } catch (Throwable $error) { $this->conn->rollback(); throw $error; }
    }

    public function statuses(): array { return ['pending','confirmed','shipping','delivered','success','cancelled']; }
    public function statusText(string $status): string { return ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','shipping'=>'Đang giao','delivered'=>'Đã giao','success'=>'Hoàn thành','cancelled'=>'Đã hủy'][$status] ?? $status; }
    private function allowedTransition(string $from, string $to): bool { return in_array($to, ['pending'=>['confirmed','cancelled'],'confirmed'=>['shipping','cancelled'],'shipping'=>['delivered'],'delivered'=>['success'],'success'=>[],'cancelled'=>[]][$from] ?? [], true); }
    private function restoreStock(int $orderId): void { $statement = $this->conn->prepare('SELECT variant_id, quantity FROM order_items WHERE order_id = ?'); $statement->bind_param('i', $orderId); $statement->execute(); foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $item) { if ($item['variant_id']) { $update = $this->conn->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?'); $update->bind_param('ii', $item['quantity'], $item['variant_id']); $update->execute(); } } }
}
