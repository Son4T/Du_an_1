<?php

class ColorModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function getAllColors(bool $onlyVisible = false): mysqli_result
    {
        $statusCondition = $onlyVisible ? "WHERE status = 'Hiện'" : '';
        $sql = "SELECT * FROM colors $statusCondition ORDER BY id DESC";

        return $this->conn->query($sql);
    }

    public function getColorById(int $colorId): ?array
    {
        $statement = $this->conn->prepare(
            'SELECT * FROM colors WHERE id = ?'
        );
        $statement->bind_param('i', $colorId);
        $statement->execute();

        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function createColor(
        string $colorCode,
        string $colorName,
        string $status
    ): bool {
        $statement = $this->conn->prepare(
            'INSERT INTO colors (color_code, color_name, status)
            VALUES (?, ?, ?)'
        );
        $statement->bind_param('sss', $colorCode, $colorName, $status);

        return $statement->execute();
    }

    public function updateColor(
        int $colorId,
        string $colorCode,
        string $colorName,
        string $status
    ): bool {
        $statement = $this->conn->prepare(
            'UPDATE colors
            SET color_code = ?, color_name = ?, status = ?
            WHERE id = ?'
        );
        $statement->bind_param(
            'sssi',
            $colorCode,
            $colorName,
            $status,
            $colorId
        );

        return $statement->execute();
    }

    public function deleteColor(int $colorId): bool
    {
        $countStatement = $this->conn->prepare(
            'SELECT COUNT(*) AS total
            FROM product_variants
            WHERE color_id = ?'
        );
        $countStatement->bind_param('i', $colorId);
        $countStatement->execute();

        $variantCount = (int) $countStatement
            ->get_result()
            ->fetch_assoc()['total'];

        if ($variantCount > 0) {
            return false;
        }

        $deleteStatement = $this->conn->prepare(
            'DELETE FROM colors WHERE id = ?'
        );
        $deleteStatement->bind_param('i', $colorId);

        return $deleteStatement->execute();
    }
}
