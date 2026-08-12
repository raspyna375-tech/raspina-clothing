-- Raspina Clothing catalogue schema
-- Default table prefix: rc_
-- Use database/import-catalog.php when a different validated prefix is configured.
-- This file intentionally contains no CREATE DATABASE, USE, users or credentials.

CREATE TABLE IF NOT EXISTS `rc_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(191) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `description` TEXT NOT NULL,
    `parent_slug` VARCHAR(191) NULL,
    `product_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`),
    KEY `idx_categories_parent` (`parent_slug`),
    KEY `idx_categories_count` (`product_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_id` BIGINT UNSIGNED NULL,
    `slug` VARCHAR(191) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `summary` TEXT NOT NULL,
    `description` MEDIUMTEXT NOT NULL,
    `sku` VARCHAR(96) NOT NULL DEFAULT '',
    `product_type` VARCHAR(50) NOT NULL DEFAULT 'simple',
    `reference_price` DECIMAL(18,4) NULL,
    `regular_price` DECIMAL(18,4) NULL,
    `sale_price` DECIMAL(18,4) NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `stock_status` VARCHAR(32) NOT NULL DEFAULT 'outofstock',
    `stock_quantity` INT NULL,
    `featured` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `seo_title` VARCHAR(255) NOT NULL DEFAULT '',
    `seo_description` VARCHAR(500) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_slug` (`slug`),
    UNIQUE KEY `uq_products_source_id` (`source_id`),
    KEY `idx_products_sku` (`sku`),
    KEY `idx_products_stock` (`stock_status`),
    KEY `idx_products_featured_created` (`featured`, `created_at`),
    KEY `idx_products_reference_price` (`reference_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_product_categories` (
    `product_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`product_id`, `category_id`),
    KEY `idx_product_categories_category` (`category_id`, `product_id`),
    FOREIGN KEY (`product_id`) REFERENCES `rc_products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `rc_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_product_images` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_product_image_order` (`product_id`, `sort_order`),
    KEY `idx_product_images_product` (`product_id`),
    FOREIGN KEY (`product_id`) REFERENCES `rc_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_product_attributes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `attribute_name` VARCHAR(191) NOT NULL,
    `attribute_value` VARCHAR(500) NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_product_attributes_product` (`product_id`, `sort_order`),
    KEY `idx_product_attributes_name` (`attribute_name`),
    FOREIGN KEY (`product_id`) REFERENCES `rc_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_product_tags` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `tag_name` VARCHAR(191) NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_product_tags_product` (`product_id`, `sort_order`),
    KEY `idx_product_tags_name` (`tag_name`),
    FOREIGN KEY (`product_id`) REFERENCES `rc_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_type` VARCHAR(32) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `business_name` VARCHAR(120) NOT NULL DEFAULT '',
    `email` VARCHAR(190) NOT NULL,
    `phone` VARCHAR(60) NOT NULL DEFAULT '',
    `country` VARCHAR(100) NOT NULL DEFAULT '',
    `message` TEXT NOT NULL,
    `products_json` TEXT NOT NULL,
    `ip_hash` CHAR(64) NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_messages_type_created` (`message_type`, `created_at`),
    KEY `idx_messages_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_meta` (
    `meta_key` VARCHAR(191) NOT NULL,
    `meta_value` MEDIUMTEXT NOT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
