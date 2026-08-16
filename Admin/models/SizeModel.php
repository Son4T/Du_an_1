<?php

class SizeModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function getAllSizes(bool $onlyVisible = false): mysqli_result
    {
        $statusCondition = $onlyVisible ? "WHERE status = 'Hiện'" : '';
        $sql = "SELECT *
                FROM sizes
                $statusCondition
                ORDER BY sort_order, id DESC";

        return $this->conn->query($sql);
    }

    public function getSizeById(int $sizeId): ?array
    {
        $statement = $this->conn->prepare(
            'SELECT * FROM sizes WHERE id = ?'
        );
        $statement->bind_param('i', $sizeId);
        $statement->execute();

        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function createSize(
        string $sizeCode,
        string $sizeName,
        string $status
    ): bool {
        $statement = $this->conn->prepare(
            'INSERT INTO sizes (size_code, size_name, status)
            VALUES (?, ?, ?)'
        );
        $statement->bind_param('sss', $sizeCode, $sizeName, $status);

        return $statement->execute();
    }

    public function updateSize(
        int $sizeId,
        string $sizeCode,
        string $sizeName,
        string $status
    ): bool {
        $statement = $this->conn->prepare(
            'UPDATE sizes
            SET size_code = ?, size_name = ?, status = ?
            WHERE id = ?'
        );
        $statement->bind_param(
            'sssi',
            $sizeCode,
            $sizeName,
            $status,
            $sizeId
        );

        return $statement->execute();
    }

    public function deleteSize(int $sizeId): bool
    {
        $countStatement = $this->conn->prepare(
            'SELECT COUNT(*) AS total
            FROM product_variants
            WHERE size_id = ?'
        );
        $countStatement->bind_param('i', $sizeId);
        $countStatement->execute();

        $variantCount = (int) $countStatement
            ->get_result()
            ->fetch_assoc()['total'];

        if ($variantCount > 0) {
            return false;
        }

        $deleteStatement = $this->conn->prepare(
            'DELETE FROM sizes WHERE id = ?'
        );
        $deleteStatement->bind_param('i', $sizeId);

        return $deleteStatement->execute();
    }
}
