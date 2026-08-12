-- Raspina Clothing admin schema
-- Import this after database/schema.sql using the configured table prefix.
-- Default table prefix is rc_. This file contains no credentials or users.

CREATE TABLE IF NOT EXISTS `rc_admin_users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(190) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(32) NOT NULL DEFAULT 'owner',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admin_users_email` (`email`),
    KEY `idx_admin_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rc_admin_audit` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `ip_hash` CHAR(64) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_admin_audit_admin_created` (`admin_user_id`, `created_at`),
    KEY `idx_admin_audit_entity` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_admin_audit_user` FOREIGN KEY (`admin_user_id`) REFERENCES `rc_admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
