<?php

class ProductModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function createProduct(array $productData, array $variants): bool
    {
        $this->conn->begin_transaction();

        try {
            // Bước 1: Thêm thông tin chung của sản phẩm.
            $sql = 'INSERT INTO products (
                        category_id,
                        product_code,
                        name,
                        slug,
                        description,
                        base_price,
                        sale_price,
                        image_url,
                        status,
                        featured
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $statement = $this->conn->prepare($sql);
            $statement->bind_param(
                'issssddssi',
                $productData['category_id'],
                $productData['code'],
                $productData['name'],
                $productData['slug'],
                $productData['description'],
                $productData['base_price'],
                $productData['sale_price'],
                $productData['image_url'],
                $productData['status'],
                $productData['featured']
            );
            $statement->execute();

            // Bước 2: Lấy id sản phẩm vừa thêm để thêm các biến thể.
            $productId = $this->conn->insert_id;
            $this->insertVariants($productId, $variants);

            $this->conn->commit();
            return true;
        } catch (Throwable $error) {
            $this->conn->rollback();
            error_log($error->getMessage());
            return false;
        }
    }

    private function insertVariants(int $productId, array $variants): void
    {
        $sql = 'INSERT INTO product_variants (
                    product_id,
                    color_id,
                    size_id,
                    sku,
                    price,
                    stock,
                    image_url
                ) VALUES (?, ?, ?, ?, ?, ?, ?)';

        $statement = $this->conn->prepare($sql);

        foreach ($variants as $variant) {
            $statement->bind_param(
                'iiisdis',
                $productId,
                $variant['color_id'],
                $variant['size_id'],
                $variant['sku'],
                $variant['price'],
                $variant['stock'],
                $variant['image_url']
            );
            $statement->execute();
        }
    }

    public function updateProduct(
        int $productId,
        array $productData,
        array $variants
    ): bool {
        $this->conn->begin_transaction();

        try {
            // Bước 1: Cập nhật thông tin chung của sản phẩm.
            $sql = 'UPDATE products SET
                        category_id = ?,
                        product_code = ?,
                        name = ?,
                        slug = ?,
                        description = ?,
                        base_price = ?,
                        sale_price = ?,
                        image_url = ?,
                        status = ?,
                        featured = ?
                    WHERE id = ?';

            $statement = $this->conn->prepare($sql);
            $statement->bind_param(
                'issssddssii',
                $productData['category_id'],
                $productData['code'],
                $productData['name'],
                $productData['slug'],
                $productData['description'],
                $productData['base_price'],
                $productData['sale_price'],
                $productData['image_url'],
                $productData['status'],
                $productData['featured'],
                $productId
            );
            $statement->execute();

            // Bước 2: Lấy các biến thể cũ và ghép theo cặp màu - kích thước.
            $existingVariants = $this->getExistingVariantMap($productId);
            $keptVariantIds = [];

            // Bước 3: Cập nhật biến thể cũ hoặc thêm biến thể mới.
            foreach ($variants as $variant) {
                $variantKey = $variant['color_id'] . '-' . $variant['size_id'];

                if (isset($existingVariants[$variantKey])) {
                    $variantId = $existingVariants[$variantKey];
                    $this->updateVariant($variantId, $variant);
                    $keptVariantIds[] = $variantId;
                    continue;
                }

                $keptVariantIds[] = $this->createVariant($productId, $variant);
            }

            // Bước 4: Biến thể bị bỏ khỏi form sẽ khóa tồn kho hoặc xóa nếu chưa có đơn.
            $this->removeUnusedVariants($productId, $keptVariantIds);

            $this->conn->commit();
            return true;
        } catch (Throwable $error) {
            $this->conn->rollback();
            error_log($error->getMessage());
            return false;
        }
    }

    private function getExistingVariantMap(int $productId): array
    {
        $statement = $this->conn->prepare(
            'SELECT id, color_id, size_id
             FROM product_variants
             WHERE product_id = ?'
        );
        $statement->bind_param('i', $productId);
        $statement->execute();

        $variantMap = [];
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as $row) {
            $key = $row['color_id'] . '-' . $row['size_id'];
            $variantMap[$key] = (int) $row['id'];
        }

        return $variantMap;
    }

    private function updateVariant(int $variantId, array $variant): void
    {
        $statement = $this->conn->prepare(
            'UPDATE product_variants
             SET sku = ?, price = ?, stock = ?, image_url = ?
             WHERE id = ?'
        );
        $statement->bind_param(
            'sdisi',
            $variant['sku'],
            $variant['price'],
            $variant['stock'],
            $variant['image_url'],
            $variantId
        );
        $statement->execute();
    }

    private function createVariant(int $productId, array $variant): int
    {
        $statement = $this->conn->prepare(
            'INSERT INTO product_variants (
                product_id,
                color_id,
                size_id,
                sku,
                price,
                stock,
                image_url
             ) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param(
            'iiisdis',
            $productId,
            $variant['color_id'],
            $variant['size_id'],
            $variant['sku'],
            $variant['price'],
            $variant['stock'],
            $variant['image_url']
        );
        $statement->execute();

        return $this->conn->insert_id;
    }

    private function removeUnusedVariants(
        int $productId,
        array $keptVariantIds
    ): void {
        // Không xóa biến thể đã từng nằm trong đơn hàng để giữ đúng lịch sử.
        if (empty($keptVariantIds)) {
            $disableStatement = $this->conn->prepare(
                'UPDATE product_variants
                 SET stock = 0
                 WHERE product_id = ?'
            );
            $disableStatement->bind_param('i', $productId);
            $disableStatement->execute();

            $deleteStatement = $this->conn->prepare(
                'DELETE product_variant
                 FROM product_variants product_variant
                 LEFT JOIN order_items order_item
                    ON order_item.variant_id = product_variant.id
                 WHERE product_variant.product_id = ?
                   AND order_item.id IS NULL'
            );
            $deleteStatement->bind_param('i', $productId);
            $deleteStatement->execute();
            return;
        }

        $placeholders = implode(',', array_fill(0, count($keptVariantIds), '?'));
        $parameterTypes = 'i' . str_repeat('i', count($keptVariantIds));
        $parameters = array_merge([$productId], $keptVariantIds);

        $disableStatement = $this->conn->prepare(
            "UPDATE product_variants
             SET stock = 0
             WHERE product_id = ?
               AND id NOT IN ($placeholders)"
        );
        bind_dynamic_params($disableStatement, $parameterTypes, $parameters);
        $disableStatement->execute();

        $deleteStatement = $this->conn->prepare(
            "DELETE product_variant
             FROM product_variants product_variant
             LEFT JOIN order_items order_item
                ON order_item.variant_id = product_variant.id
             WHERE product_variant.product_id = ?
               AND product_variant.id NOT IN ($placeholders)
               AND order_item.id IS NULL"
        );
        bind_dynamic_params($deleteStatement, $parameterTypes, $parameters);
        $deleteStatement->execute();
    }

    public function getAllProducts(array $filters = []): array
    {
        $sql = 'SELECT
                    product.*,
                    category.name AS category_name,
                    COALESCE(SUM(variant.stock), 0) AS total_stock,
                    COUNT(DISTINCT variant.id) AS variant_count
                FROM products product
                LEFT JOIN categories category
                    ON category.id = product.category_id
                LEFT JOIN product_variants variant
                    ON variant.product_id = product.id
                WHERE 1';

        $parameterTypes = '';
        $parameters = [];

        if (!empty($filters['keyword'])) {
            $sql .= ' AND (
                product.name LIKE ?
                OR product.product_code LIKE ?
            )';

            $keyword = '%' . $filters['keyword'] . '%';
            $parameterTypes .= 'ss';
            $parameters[] = $keyword;
            $parameters[] = $keyword;
        }

        if (!empty($filters['category_id'])) {
            $sql .= ' AND product.category_id = ?';
            $parameterTypes .= 'i';
            $parameters[] = (int) $filters['category_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= ' AND product.status = ?';
            $parameterTypes .= 's';
            $parameters[] = $filters['status'];
        }

        $sql .= ' GROUP BY product.id ORDER BY product.id DESC';

        $statement = $this->conn->prepare($sql);
        bind_dynamic_params($statement, $parameterTypes, $parameters);
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductWithVariants(int $productId): ?array
    {
        $statement = $this->conn->prepare(
            'SELECT
                product.*,
                category.name AS category_name
             FROM products product
             LEFT JOIN categories category
                ON category.id = product.category_id
             WHERE product.id = ?'
        );
        $statement->bind_param('i', $productId);
        $statement->execute();

        $product = $statement->get_result()->fetch_assoc();

        if (!$product) {
            return null;
        }

        $variantStatement = $this->conn->prepare(
            'SELECT
                variant.*,
                color.color_name,
                color.color_code,
                size.size_name,
                size.size_code
             FROM product_variants variant
             JOIN colors color ON color.id = variant.color_id
             JOIN sizes size ON size.id = variant.size_id
             WHERE variant.product_id = ?
             ORDER BY color.color_name, size.sort_order, size.size_name'
        );
        $variantStatement->bind_param('i', $productId);
        $variantStatement->execute();

        $product['variants'] = $variantStatement
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        return $product;
    }

    public function getProductById(
        int $productId,
        bool $activeOnly = false
    ): ?array {
        $sql = 'SELECT
                    product.*,
                    category.name AS category_name,
                    COALESCE(SUM(variant.stock), 0) AS total_stock
                FROM products product
                LEFT JOIN categories category
                    ON category.id = product.category_id
                LEFT JOIN product_variants variant
                    ON variant.product_id = product.id
                WHERE product.id = ?';

        if ($activeOnly) {
            $sql .= " AND product.status = 'Hiện'
                      AND category.status = 'active'";
        }

        $sql .= ' GROUP BY product.id';

        $statement = $this->conn->prepare($sql);
        $statement->bind_param('i', $productId);
        $statement->execute();

        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function getVariantById(int $variantId): ?array
    {
        $sql = "SELECT
                    variant.*,
                    product.name AS product_name,
                    IF(
                        product.status = 'Hiện'
                        AND category.status = 'active',
                        'Hiện',
                        'Ẩn'
                    ) AS product_status,
                    color.color_name,
                    color.color_code,
                    size.size_name,
                    size.size_code
                FROM product_variants variant
                JOIN products product ON product.id = variant.product_id
                JOIN categories category ON category.id = product.category_id
                JOIN colors color ON color.id = variant.color_id
                JOIN sizes size ON size.id = variant.size_id
                WHERE variant.id = ?";

        $statement = $this->conn->prepare($sql);
        $statement->bind_param('i', $variantId);
        $statement->execute();

        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function catalog(
        array $filters,
        int $page = 1,
        int $limit = 12
    ): array {
        $whereSql = "product.status = 'Hiện'
            AND EXISTS (
                SELECT 1
                FROM categories active_category
                WHERE active_category.id = product.category_id
                  AND active_category.status = 'active'
            )";

        $parameterTypes = '';
        $parameters = [];

        if (!empty($filters['keyword'])) {
            $whereSql .= ' AND (
                product.name LIKE ?
                OR product.description LIKE ?
                OR product.product_code LIKE ?
            )';

            $keyword = '%' . $filters['keyword'] . '%';
            $parameterTypes .= 'sss';
            array_push($parameters, $keyword, $keyword, $keyword);
        }

        if (!empty($filters['category_id'])) {
            $whereSql .= ' AND product.category_id = ?';
            $parameterTypes .= 'i';
            $parameters[] = (int) $filters['category_id'];
        }

        $priceFilter = $filters['price'] ?? '';

        if ($priceFilter === '1') {
            $whereSql .= ' AND COALESCE(
                NULLIF(product.sale_price, 0),
                product.base_price
            ) < 200000';
        }

        if ($priceFilter === '2') {
            $whereSql .= ' AND COALESCE(
                NULLIF(product.sale_price, 0),
                product.base_price
            ) BETWEEN 200000 AND 500000';
        }

        if ($priceFilter === '3') {
            $whereSql .= ' AND COALESCE(
                NULLIF(product.sale_price, 0),
                product.base_price
            ) > 500000';
        }

        if (!empty($filters['in_stock'])) {
            $whereSql .= ' AND EXISTS (
                SELECT 1
                FROM product_variants stock_variant
                WHERE stock_variant.product_id = product.id
                  AND stock_variant.stock > 0
            )';
        }

        $countStatement = $this->conn->prepare(
            "SELECT COUNT(*) AS total
             FROM products product
             WHERE $whereSql"
        );
        bind_dynamic_params($countStatement, $parameterTypes, $parameters);
        $countStatement->execute();

        $totalProducts = (int) $countStatement
            ->get_result()
            ->fetch_assoc()['total'];

        $sortSql = match ($filters['sort'] ?? 'new') {
            'price_asc' => 'effective_price ASC',
            'price_desc' => 'effective_price DESC',
            'popular' => 'product.views DESC',
            default => 'product.id DESC',
        };

        $offset = max(0, ($page - 1) * $limit);

        $sql = "SELECT
                    product.*,
                    category.name AS category_name,
                    COALESCE(
                        NULLIF(product.sale_price, 0),
                        product.base_price
                    ) AS effective_price,
                    COALESCE(SUM(variant.stock), 0) AS total_stock,
                    (
                        SELECT AVG(review.rating)
                        FROM comments review
                        WHERE review.product_id = product.id
                          AND review.status = 'approved'
                    ) AS avg_rating
                FROM products product
                LEFT JOIN categories category
                    ON category.id = product.category_id
                LEFT JOIN product_variants variant
                    ON variant.product_id = product.id
                WHERE $whereSql
                GROUP BY product.id
                ORDER BY $sortSql
                LIMIT ?, ?";

        $listParameterTypes = $parameterTypes . 'ii';
        $listParameters = array_merge($parameters, [$offset, $limit]);

        $statement = $this->conn->prepare($sql);
        bind_dynamic_params(
            $statement,
            $listParameterTypes,
            $listParameters
        );
        $statement->execute();

        return [
            'items' => $statement->get_result()->fetch_all(MYSQLI_ASSOC),
            'total' => $totalProducts,
            'pages' => max(1, (int) ceil($totalProducts / $limit)),
        ];
    }

    public function featured(int $limit = 8): array
    {
        $sql = "SELECT
                    product.*,
                    category.name AS category_name,
                    COALESCE(
                        NULLIF(product.sale_price, 0),
                        product.base_price
                    ) AS effective_price,
                    COALESCE(SUM(variant.stock), 0) AS total_stock
                FROM products product
                LEFT JOIN categories category
                    ON category.id = product.category_id
                LEFT JOIN product_variants variant
                    ON variant.product_id = product.id
                WHERE product.status = 'Hiện'
                  AND category.status = 'active'
                GROUP BY product.id
                ORDER BY product.featured DESC, product.id DESC
                LIMIT ?";

        $statement = $this->conn->prepare($sql);
        $statement->bind_param('i', $limit);
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function related(
        int $productId,
        ?int $categoryId,
        int $limit = 4
    ): array {
        $categoryId = $categoryId ?: 0;

        $sql = "SELECT
                    product.*,
                    COALESCE(
                        NULLIF(product.sale_price, 0),
                        product.base_price
                    ) AS effective_price,
                    COALESCE(SUM(variant.stock), 0) AS total_stock
                FROM products product
                JOIN categories category
                    ON category.id = product.category_id
                LEFT JOIN product_variants variant
                    ON variant.product_id = product.id
                WHERE product.status = 'Hiện'
                  AND category.status = 'active'
                  AND product.id <> ?
                  AND (? = 0 OR product.category_id = ?)
                GROUP BY product.id
                ORDER BY product.id DESC
                LIMIT ?";

        $statement = $this->conn->prepare($sql);
        $statement->bind_param(
            'iiii',
            $productId,
            $categoryId,
            $categoryId,
            $limit
        );
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function increaseViews(int $productId): void
    {
        $statement = $this->conn->prepare(
            'UPDATE products SET views = views + 1 WHERE id = ?'
        );
        $statement->bind_param('i', $productId);
        $statement->execute();
    }

    /**
     * Xóa sản phẩm nhưng vẫn giữ thông tin trong các đơn hàng cũ.
     *
     * Bảng order_items đã lưu sẵn tên sản phẩm, biến thể, SKU và giá bán.
     * Vì vậy trước khi xóa, ta chỉ bỏ liên kết product_id và variant_id.
     */
    public function deleteProduct(int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        $this->conn->begin_transaction();

        try {
            // Bước 1: Kiểm tra sản phẩm có tồn tại hay không.
            $checkStatement = $this->conn->prepare(
                'SELECT id FROM products WHERE id = ?'
            );
            $checkStatement->bind_param('i', $productId);
            $checkStatement->execute();

            if (!$checkStatement->get_result()->fetch_assoc()) {
                throw new RuntimeException('Sản phẩm không tồn tại.');
            }

            // Bước 2: Bỏ liên kết biến thể trong các đơn hàng cũ.
            // Thông tin tên, SKU, giá vẫn còn trong bảng order_items.
            $variantStatement = $this->conn->prepare(
                'UPDATE order_items
                 SET variant_id = NULL
                 WHERE variant_id IN (
                    SELECT id
                    FROM product_variants
                    WHERE product_id = ?
                 )'
            );
            $variantStatement->bind_param('i', $productId);
            $variantStatement->execute();

            // Bước 3: Bỏ liên kết sản phẩm trong các đơn hàng cũ.
            $orderItemStatement = $this->conn->prepare(
                'UPDATE order_items
                 SET product_id = NULL
                 WHERE product_id = ?'
            );
            $orderItemStatement->bind_param('i', $productId);
            $orderItemStatement->execute();

            // Bước 4: Xóa bình luận và các biến thể của sản phẩm.
            $commentStatement = $this->conn->prepare(
                'DELETE FROM comments WHERE product_id = ?'
            );
            $commentStatement->bind_param('i', $productId);
            $commentStatement->execute();

            $variantDeleteStatement = $this->conn->prepare(
                'DELETE FROM product_variants WHERE product_id = ?'
            );
            $variantDeleteStatement->bind_param('i', $productId);
            $variantDeleteStatement->execute();

            // Bước 5: Xóa sản phẩm.
            $productStatement = $this->conn->prepare(
                'DELETE FROM products WHERE id = ?'
            );
            $productStatement->bind_param('i', $productId);
            $productStatement->execute();

            if ($productStatement->affected_rows !== 1) {
                throw new RuntimeException('Không thể xóa sản phẩm.');
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $error) {
            $this->conn->rollback();
            error_log($error->getMessage());
            return false;
        }
    }

    public function toggleStatus(int $productId): bool
    {
        $statement = $this->conn->prepare(
            "UPDATE products
             SET status = IF(status = 'Hiện', 'Ẩn', 'Hiện')
             WHERE id = ?"
        );
        $statement->bind_param('i', $productId);

        return $statement->execute();
    }
}
