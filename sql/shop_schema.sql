CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    cover_image VARCHAR(255) NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    short_description VARCHAR(255) NULL,
    description TEXT NULL,
    price DECIMAL(12,2) NOT NULL,
    discount_price DECIMAL(12,2) NULL,
    stock_qty INT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    main_image VARCHAR(255) NOT NULL,
    video_url VARCHAR(500) NULL,
    rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_products_category (category_id),
    INDEX idx_products_status (status),
    INDEX idx_products_featured (featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_product_images_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    device_token VARCHAR(80) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_ratings_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uniq_product_ip_device (product_id, ip_address, device_token),
    INDEX idx_product_ratings_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_views_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uniq_product_ip (product_id, ip_address),
    INDEX idx_product_views_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(160) NULL,
    full_name VARCHAR(160) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'admin',
    status TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admins_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gadget_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(140) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(190) NULL,
    gadget_name VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    status ENUM('pending', 'done') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gadget_requests_status (status),
    INDEX idx_gadget_requests_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
