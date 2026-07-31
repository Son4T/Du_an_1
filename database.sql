-- URBAN TRIBE - DATABASE CÀI MỚI
-- Import file này trong phpMyAdmin nếu muốn cài mới hoàn toàn.

CREATE DATABASE IF NOT EXISTS urban_tribe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE urban_tribe;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS sizes;
DROP TABLE IF EXISTS colors;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    address VARCHAR(500) DEFAULT '',
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    status ENUM('active','locked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(500) DEFAULT '',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE colors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    color_code VARCHAR(7) NOT NULL,
    color_name VARCHAR(80) NOT NULL UNIQUE,
    status ENUM('Hiện','Ẩn') NOT NULL DEFAULT 'Hiện'
) ENGINE=InnoDB;

CREATE TABLE sizes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    size_code VARCHAR(20) NOT NULL UNIQUE,
    size_name VARCHAR(80) NOT NULL,
    status ENUM('Hiện','Ẩn') NOT NULL DEFAULT 'Hiện',
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    product_code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    base_price DECIMAL(12,0) NOT NULL DEFAULT 0,
    sale_price DECIMAL(12,0) NOT NULL DEFAULT 0,
    image_url VARCHAR(500) DEFAULT '',
    status ENUM('Hiện','Ẩn') NOT NULL DEFAULT 'Hiện',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_products_catalog (status, category_id, created_at),
    INDEX idx_products_price (base_price, sale_price)
) ENGINE=InnoDB;

CREATE TABLE product_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    color_id INT UNSIGNED NOT NULL,
    size_id INT UNSIGNED NOT NULL,
    sku VARCHAR(60) NOT NULL UNIQUE,
    price DECIMAL(12,0) NOT NULL DEFAULT 0,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_variants_color FOREIGN KEY (color_id) REFERENCES colors(id),
    CONSTRAINT fk_variants_size FOREIGN KEY (size_id) REFERENCES sizes(id),
    UNIQUE KEY uq_product_color_size (product_id, color_id, size_id),
    INDEX idx_variants_stock (stock)
) ENGINE=InnoDB;

CREATE TABLE orders (
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
    payment_reference VARCHAR(50) DEFAULT '',
    zalopay_app_trans_id VARCHAR(40) NULL,
    zalopay_app_user VARCHAR(50) DEFAULT '',
    zalopay_zp_trans_id VARCHAR(100) DEFAULT '',
    zalopay_order_url TEXT NULL,
    zalopay_order_token TEXT NULL,
    zalopay_qr_code TEXT NULL,
    zalopay_return_code INT NULL,
    payment_message VARCHAR(255) DEFAULT '',
    paid_at DATETIME NULL,
    payment_updated_at DATETIME NULL,
    subtotal DECIMAL(12,0) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(12,0) NOT NULL DEFAULT 0,
    total DECIMAL(12,0) NOT NULL DEFAULT 0,
    status ENUM('pending','confirmed','shipping','delivered','success','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_orders_zalopay_app_trans_id (zalopay_app_trans_id),
    INDEX idx_orders_user (user_id, created_at),
    INDEX idx_orders_status (status, payment_status, created_at),
    INDEX idx_orders_zalopay_trans (zalopay_zp_trans_id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
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
    INDEX idx_items_order (order_id),
    INDEX idx_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    content VARCHAR(1000) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    status ENUM('pending','approved','hidden') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uq_comment_user_product (user_id, product_id),
    INDEX idx_comments_product_status (product_id, status)
) ENGINE=InnoDB;

CREATE TABLE contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    subject VARCHAR(200) NOT NULL,
    message VARCHAR(2000) NOT NULL,
    status ENUM('new','processing','done') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contacts_status (status, created_at)
) ENGINE=InnoDB;

-- Tài khoản mẫu
INSERT INTO users (username,email,password_hash,full_name,phone,address,role,status) VALUES
('admin','admin@urbantribe.local','$2y$12$0IdofxJ/Es0nMjPauH9JSu9.ADZQDHhk4AD0ANUdEIQ1wL1d0xEs.','Quản trị Urban Tribe','0988888888','Hà Nội','admin','active'),
('khachhang','user@urbantribe.local','$2y$12$be5s3y3Rn9Z907n.7ktOrO.VAN9/y.r4J0X1WynN7NIPdr3B0Ohe6','Khách hàng mẫu','0912345678','Cầu Giấy, Hà Nội','user','active');

INSERT INTO categories (name,slug,description,status,sort_order) VALUES
('Áo thun','ao-thun','Áo thun trẻ trung, dễ phối đồ.','active',1),
('Áo sơ mi','ao-so-mi','Sơ mi thanh lịch cho nhiều hoàn cảnh.','active',2),
('Giày','giay','Giày thời trang và năng động.','active',3),
('Phụ kiện','phu-kien','Phụ kiện hoàn thiện phong cách.','active',4);

INSERT INTO colors (color_code,color_name,status) VALUES
('#111827','Đen','Hiện'),('#FFFFFF','Trắng','Hiện'),('#1D4ED8','Xanh dương','Hiện'),('#BE123C','Đỏ đô','Hiện'),('#D6D3D1','Be','Hiện');

INSERT INTO sizes (size_code,size_name,status,sort_order) VALUES
('S','Size S','Hiện',1),('M','Size M','Hiện',2),('L','Size L','Hiện',3),('XL','Size XL','Hiện',4),('39','Size giày 39','Hiện',10),('40','Size giày 40','Hiện',11),('41','Size giày 41','Hiện',12),('F','Freesize','Hiện',20);

INSERT INTO products (category_id,product_code,name,slug,description,base_price,sale_price,image_url,status,featured,views) VALUES
(1,'UT-TS-001','Áo thun Urban Basic','ao-thun-urban-basic','Áo thun cotton form rộng, chất vải mềm và thoáng. Phù hợp mặc hằng ngày.',249000,199000,'public/uploads/products/1776431525-photo_2026-04-17_20-09-08.jpg','Hiện',1,35),
(2,'UT-SM-001','Sơ mi Minimal Form','so-mi-minimal-form','Sơ mi phong cách tối giản, phom dễ mặc và phù hợp đi học, đi làm.',399000,0,'public/uploads/products/1765243671-somi.jpg','Hiện',1,24),
(3,'UT-GI-001','Giày Canvas Daisy','giay-canvas-daisy','Giày canvas họa tiết hoa cúc, đế êm và phối đồ linh hoạt.',599000,529000,'public/uploads/products/1765246148-giay-hoa-cuc-chinh-hang-yenlanh.com-9027.jpg','Hiện',1,48),
(1,'UT-TS-002','Áo thun Graphic Street','ao-thun-graphic-street','Thiết kế graphic nổi bật dành cho phong cách đường phố.',289000,0,'public/uploads/products/1776171262-gen-h-z7436444705519_f17b25bfe44f98aac27123f24ff4fd2f.jpg','Hiện',0,17),
(2,'UT-SM-002','Sơ mi Relaxed Beige','so-mi-relaxed-beige','Sơ mi tông be nhẹ nhàng, form relaxed thoải mái.',429000,379000,'public/uploads/products/1776502279-1765247871-somi.jpg','Hiện',0,19),
(4,'UT-PK-001','Túi đeo chéo Urban','tui-deo-cheo-urban','Túi đeo chéo nhỏ gọn, nhiều ngăn tiện dụng.',319000,0,'public/uploads/products/1776431265-lzPYVz9vaXFjmhtYOXbPcECn1E4Q49IE.webp','Hiện',1,22);

INSERT INTO product_variants (product_id,color_id,size_id,sku,price,stock) VALUES
(1,1,1,'UT-TS-001-BL-S',249000,12),(1,1,2,'UT-TS-001-BL-M',249000,18),(1,2,2,'UT-TS-001-WH-M',249000,14),(1,2,3,'UT-TS-001-WH-L',249000,9),
(2,2,2,'UT-SM-001-WH-M',399000,10),(2,2,3,'UT-SM-001-WH-L',399000,8),(2,3,3,'UT-SM-001-BU-L',399000,6),
(3,2,5,'UT-GI-001-WH-39',599000,7),(3,2,6,'UT-GI-001-WH-40',599000,11),(3,1,7,'UT-GI-001-BL-41',599000,5),
(4,1,2,'UT-TS-002-BL-M',289000,15),(4,4,3,'UT-TS-002-RD-L',289000,10),
(5,5,2,'UT-SM-002-BE-M',429000,7),(5,5,3,'UT-SM-002-BE-L',429000,4),
(6,1,8,'UT-PK-001-BL-F',319000,13),(6,5,8,'UT-PK-001-BE-F',319000,8);

INSERT INTO comments (user_id,product_id,content,rating,status) VALUES
(2,1,'Áo mặc thoải mái, form đẹp và đúng kích thước mô tả.',5,'approved');
