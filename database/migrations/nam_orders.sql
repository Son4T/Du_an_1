-- Migration phần Nam: bảng đơn hàng và chi tiết đơn hàng.
-- Chạy sau migration sản phẩm, biến thể và tài khoản.
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(30) NOT NULL UNIQUE,
    user_id INT UNSIGNED NULL,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(120) DEFAULT '',
    address VARCHAR(500) NOT NULL,
    note VARCHAR(1000) DEFAULT '',
    payment_method ENUM('cod','zalopay') NOT NULL DEFAULT 'cod',
    payment_status ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
    subtotal DECIMAL(12,0) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(12,0) NOT NULL DEFAULT 0,
    total DECIMAL(12,0) NOT NULL DEFAULT 0,
    status ENUM('pending','confirmed','shipping','delivered','success','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_orders_user (user_id, created_at),
    INDEX idx_orders_status (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    variant_id INT UNSIGNED NULL,
    product_name VARCHAR(180) NOT NULL,
    variant_label VARCHAR(180) DEFAULT '',
    sku VARCHAR(60) DEFAULT '',
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(12,0) NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT fk_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_items_order (order_id)
) ENGINE=InnoDB;
