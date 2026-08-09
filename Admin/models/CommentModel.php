<?php

class CommentModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT
                    comment_data.*,
                    user_data.username,
                    user_data.full_name,
                    product_data.name AS product_name
                FROM comments comment_data
                JOIN users user_data ON user_data.id = comment_data.user_id
                JOIN products product_data ON product_data.id = comment_data.product_id
                WHERE 1';

        $types = '';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND comment_data.status = ?';
            $types .= 's';
            $params[] = $filters['status'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= ' AND (
                user_data.username LIKE ?
                OR product_data.name LIKE ?
                OR comment_data.content LIKE ?
            )';

            $types .= 'sss';
            $keyword = '%' . $filters['keyword'] . '%';
            array_push($params, $keyword, $keyword, $keyword);
        }

        $sql .= ' ORDER BY comment_data.id DESC';

        $statement = $this->conn->prepare($sql);
        bind_dynamic_params($statement, $types, $params);
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function approvedForProduct(int $productId): array
    {
        $statement = $this->conn->prepare(
            "SELECT
                comment_data.*,
                user_data.username,
                user_data.full_name
            FROM comments comment_data
            JOIN users user_data ON user_data.id = comment_data.user_id
            WHERE comment_data.product_id = ?
              AND comment_data.status = 'approved'
            ORDER BY comment_data.id DESC"
        );
        $statement->bind_param('i', $productId);
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(
        int $userId,
        int $productId,
        string $content,
        int $rating
    ): bool {
        // Mỗi người chỉ có một đánh giá cho một sản phẩm.
        // Gửi lại sẽ cập nhật nội dung cũ và chuyển về chờ duyệt.
        $statement = $this->conn->prepare(
            "INSERT INTO comments
                (user_id, product_id, content, rating, status)
            VALUES (?, ?, ?, ?, 'pending')
            ON DUPLICATE KEY UPDATE
                content = VALUES(content),
                rating = VALUES(rating),
                status = 'pending',
                updated_at = CURRENT_TIMESTAMP"
        );
        $statement->bind_param(
            'iisi',
            $userId,
            $productId,
            $content,
            $rating
        );

        return $statement->execute();
    }

    public function status(int $commentId, string $status): bool
    {
        $statement = $this->conn->prepare(
            'UPDATE comments SET status = ? WHERE id = ?'
        );
        $statement->bind_param('si', $status, $commentId);

        return $statement->execute();
    }

    public function delete(int $commentId): bool
    {
        $statement = $this->conn->prepare(
            'DELETE FROM comments WHERE id = ?'
        );
        $statement->bind_param('i', $commentId);

        return $statement->execute();
    }
}
