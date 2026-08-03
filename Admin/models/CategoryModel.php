<?php

class CategoryModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function all(bool $onlyActive = false): array
    {
        $statusCondition = $onlyActive
            ? "WHERE category.status = 'active'"
            : '';

        $sql = "SELECT
                    category.*,
                    COUNT(product.id) AS product_count
                FROM categories category
                LEFT JOIN products product
                    ON product.category_id = category.id
                $statusCondition
                GROUP BY category.id
                ORDER BY category.sort_order, category.id DESC";

        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function find(int $categoryId): ?array
    {
        $statement = $this->conn->prepare(
            'SELECT * FROM categories WHERE id = ?'
        );
        $statement->bind_param('i', $categoryId);
        $statement->execute();

        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $categoryData): bool
    {
        $statement = $this->conn->prepare(
            'INSERT INTO categories
            (name, slug, description, status, sort_order)
            VALUES (?, ?, ?, ?, ?)'
        );

        $statement->bind_param(
            'ssssi',
            $categoryData['name'],
            $categoryData['slug'],
            $categoryData['description'],
            $categoryData['status'],
            $categoryData['sort_order']
        );

        return $statement->execute();
    }

    public function update(int $categoryId, array $categoryData): bool
    {
        $statement = $this->conn->prepare(
            'UPDATE categories
            SET name = ?,
                slug = ?,
                description = ?,
                status = ?,
                sort_order = ?
            WHERE id = ?'
        );

        $statement->bind_param(
            'ssssii',
            $categoryData['name'],
            $categoryData['slug'],
            $categoryData['description'],
            $categoryData['status'],
            $categoryData['sort_order'],
            $categoryId
        );

        return $statement->execute();
    }

    public function delete(int $categoryId): bool
    {
        // Không xóa danh mục đang có sản phẩm.
        $countStatement = $this->conn->prepare(
            'SELECT COUNT(*) AS total
            FROM products
            WHERE category_id = ?'
        );
        $countStatement->bind_param('i', $categoryId);
        $countStatement->execute();

        $productCount = (int) $countStatement
            ->get_result()
            ->fetch_assoc()['total'];

        if ($productCount > 0) {
            return false;
        }

        $deleteStatement = $this->conn->prepare(
            'DELETE FROM categories WHERE id = ?'
        );
        $deleteStatement->bind_param('i', $categoryId);

        return $deleteStatement->execute();
    }
}
