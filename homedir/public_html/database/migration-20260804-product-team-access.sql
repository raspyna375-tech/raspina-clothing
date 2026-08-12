-- Raspina product + team access enhancement
-- MySQL-compatible migration: ADD COLUMN IF NOT EXISTS is not supported on
-- the hosting server, so ALTER statements intentionally use plain ADD COLUMN.
-- Run this file once after the previous import reported the ADD COLUMN syntax
-- error. CREATE TABLE IF NOT EXISTS and INSERT upserts make the already-created
-- migration tables safe to keep.
-- Import AFTER database/schema.sql and the existing catalogue import.
-- This migration targets the current rc_ prefix. If your configured prefix differs,
-- replace rc_ consistently before importing. It contains no credentials.

CREATE TABLE IF NOT EXISTS `rc_admin_migrations` (
    `version` VARCHAR(64) NOT NULL,
    `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `rc_products`
    ADD COLUMN `brand` VARCHAR(120) NOT NULL DEFAULT 'Raspina',
    ADD COLUMN `garment_type` VARCHAR(120) NOT NULL DEFAULT '',
    ADD COLUMN `collection_name` VARCHAR(160) NOT NULL DEFAULT '',
    ADD COLUMN `season` VARCHAR(80) NOT NULL DEFAULT '',
    ADD COLUMN `fabric_composition` VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN `fabric_weight` VARCHAR(80) NOT NULL DEFAULT '',
    ADD COLUMN `color` VARCHAR(120) NOT NULL DEFAULT '',
    ADD COLUMN `color_family` VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN `pattern` VARCHAR(120) NOT NULL DEFAULT '',
    ADD COLUMN `fit` VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN `silhouette` VARCHAR(120) NOT NULL DEFAULT '',
    ADD COLUMN `neckline` VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN `sleeve_length` VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN `garment_length` VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN `closure` VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN `lining` VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN `stretch` VARCHAR(80) NOT NULL DEFAULT '',
    ADD COLUMN `care_instructions` TEXT NOT NULL,
    ADD COLUMN `origin_country` VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN `size_range` VARCHAR(160) NOT NULL DEFAULT '',
    ADD COLUMN `customizable_size` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN `customizable_fabric` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN `minimum_order_quantity` INT UNSIGNED NULL,
    ADD COLUMN `lead_time` VARCHAR(120) NOT NULL DEFAULT '',
    ADD COLUMN `wholesale_notes` TEXT NOT NULL,
    ADD COLUMN `publish_status` VARCHAR(16) NOT NULL DEFAULT 'published',
    ADD COLUMN `available_from` DATE NULL,
    ADD COLUMN `availability_note` VARCHAR(255) NOT NULL DEFAULT '';

UPDATE `rc_products` SET `brand` = 'Raspina' WHERE `brand` = '' OR `brand` IS NULL;
UPDATE `rc_products` SET `publish_status` = 'published' WHERE `publish_status` = '' OR `publish_status` IS NULL;

ALTER TABLE `rc_product_images`
    ADD COLUMN `original_name` VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN `stored_name` VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN `mime_type` VARCHAR(80) NOT NULL DEFAULT '',
    ADD COLUMN `file_size` INT UNSIGNED NULL,
    ADD COLUMN `width` INT UNSIGNED NULL,
    ADD COLUMN `height` INT UNSIGNED NULL,
    ADD COLUMN `alt_text` VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN `uploaded_by` BIGINT UNSIGNED NULL,
    ADD COLUMN `created_at` DATETIME NULL;

CREATE TABLE IF NOT EXISTS `rc_product_variants` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `variant_sku` VARCHAR(96) NOT NULL DEFAULT '',
    `size` VARCHAR(80) NOT NULL DEFAULT '',
    `color` VARCHAR(120) NOT NULL DEFAULT '',
    `stock_status` VARCHAR(32) NOT NULL DEFAULT 'outofstock',
    `stock_quantity` INT NULL,
    `price_override` DECIMAL(18,4) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_variants_product` (`product_id`, `sort_order`),
    KEY `idx_product_variants_sku` (`variant_sku`),
    CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `rc_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `rc_admin_users`
    ADD COLUMN `title` VARCHAR(120) NOT NULL DEFAULT '',
    ADD COLUMN `role_id` BIGINT UNSIGNED NULL,
    ADD COLUMN `created_by` BIGINT UNSIGNED NULL,
    ADD COLUMN `password_changed_at` DATETIME NULL,
    ADD KEY `idx_admin_users_role` (`role_id`);

CREATE TABLE IF NOT EXISTS `rc_admin_roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(64) NOT NULL,
    `title` VARCHAR(120) NOT NULL,
    `description` VARCHAR(500) NOT NULL DEFAULT '',
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admin_roles_slug` (`slug`),
    KEY `idx_admin_roles_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_admin_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(100) NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `module` VARCHAR(80) NOT NULL,
    `action` VARCHAR(80) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admin_permissions_code` (`code`),
    KEY `idx_admin_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_admin_role_permissions` (
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `rc_admin_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `rc_admin_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_admin_user_permissions` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `effect` VARCHAR(8) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `permission_id`),
    KEY `idx_user_permissions_effect` (`effect`),
    CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `rc_admin_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `rc_admin_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `rc_admin_audit`
    ADD COLUMN `metadata_json` TEXT NULL;

INSERT INTO `rc_admin_permissions` (`code`, `title`, `module`, `action`) VALUES
    ('dashboard.view', 'View dashboard', 'Dashboard', 'View'),
    ('products.view', 'View products', 'Products', 'View'),
    ('products.create', 'Create products', 'Products', 'Create'),
    ('products.update', 'Edit products', 'Products', 'Update'),
    ('products.delete', 'Delete products', 'Products', 'Delete'),
    ('products.upload', 'Upload product media', 'Products', 'Upload'),
    ('categories.manage', 'Manage categories', 'Catalogue', 'Manage'),
    ('messages.view', 'View enquiries', 'Enquiries', 'View'),
    ('messages.delete', 'Delete enquiries', 'Enquiries', 'Delete'),
    ('settings.manage', 'Manage site settings', 'Configuration', 'Manage'),
    ('users.view', 'View team users', 'Team access', 'View'),
    ('users.create', 'Create team users', 'Team access', 'Create'),
    ('users.update', 'Edit team users', 'Team access', 'Update'),
    ('users.deactivate', 'Activate or deactivate users', 'Team access', 'Deactivate'),
    ('roles.manage', 'Manage roles and permissions', 'Team access', 'Roles'),
    ('audit.view', 'View audit activity', 'Team access', 'Audit')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `module` = VALUES(`module`), `action` = VALUES(`action`);

INSERT INTO `rc_admin_roles` (`slug`, `title`, `description`, `is_system`, `is_active`) VALUES
    ('owner', 'Owner', 'Full control of the Raspina operations console.', 1, 1),
    ('manager', 'Manager', 'Catalogue, enquiries and team operations without owner safeguards.', 1, 1),
    ('merchandiser', 'Merchandiser', 'Product stories, media and collection taxonomy.', 1, 1),
    ('viewer', 'Viewer', 'Read-only visibility into catalogue and enquiries.', 1, 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `is_system` = 1, `is_active` = 1;

INSERT IGNORE INTO `rc_admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `rc_admin_roles` r CROSS JOIN `rc_admin_permissions` p WHERE r.slug = 'owner';

INSERT IGNORE INTO `rc_admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `rc_admin_roles` r JOIN `rc_admin_permissions` p ON p.code IN (
    'dashboard.view','products.view','products.create','products.update','products.delete','products.upload',
    'categories.manage','messages.view','messages.delete','settings.manage','users.view','users.create',
    'users.update','users.deactivate','audit.view'
) WHERE r.slug = 'manager';

INSERT IGNORE INTO `rc_admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `rc_admin_roles` r JOIN `rc_admin_permissions` p ON p.code IN (
    'dashboard.view','products.view','products.create','products.update','products.upload','categories.manage'
) WHERE r.slug = 'merchandiser';

INSERT IGNORE INTO `rc_admin_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `rc_admin_roles` r JOIN `rc_admin_permissions` p ON p.code IN (
    'dashboard.view','products.view','messages.view'
) WHERE r.slug = 'viewer';

UPDATE `rc_admin_users` u JOIN `rc_admin_roles` r ON r.slug = 'owner'
SET u.role_id = r.id, u.role = 'owner', u.title = IF(u.title = '', 'Owner', u.title)
WHERE u.role_id IS NULL OR u.role = '' OR u.role = 'owner';

INSERT INTO `rc_admin_migrations` (`version`) VALUES ('20260804-product-team-access')
ON DUPLICATE KEY UPDATE `version` = VALUES(`version`);
