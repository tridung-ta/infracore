-- F1.2 / UP. Run after 001. DOWN: see ROLLBACK.md.
USE `dung_infracore`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';

CREATE TABLE `ita_assets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `asset_code` VARCHAR(64) NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `brand` VARCHAR(100) NULL,
    `model` VARCHAR(150) NULL,
    `serial_no` VARCHAR(150) NULL,
    `specs` TEXT NULL,
    `os` VARCHAR(120) NULL,
    `purchase_date` DATE NULL,
    `purchase_price` DECIMAL(18,2) NULL COMMENT 'VND',
    `warranty_end` DATE NULL,
    `vendor` VARCHAR(200) NULL,
    `department_id` BIGINT UNSIGNED NULL,
    `current_user_id` INT NULL COMMENT 'Application-validated dc_users.id',
    `status` ENUM('in_use', 'maintenance', 'broken', 'retired') NOT NULL DEFAULT 'in_use',
    `status_reason` TEXT NULL,
    `location` VARCHAR(255) NULL,
    `last_maintenance_at` DATETIME NULL,
    `next_maintenance_due` DATE NULL,
    `is_deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_by` INT NOT NULL COMMENT 'Application-validated dc_users.id',
    `updated_by` INT NULL COMMENT 'Application-validated dc_users.id',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ita_assets_store_id` (`store_id`, `id`),
    UNIQUE KEY `uq_ita_assets_store_code` (`store_id`, `asset_code`),
    KEY `idx_ita_assets_department` (`store_id`, `department_id`, `is_deleted`, `status`, `id`),
    KEY `idx_ita_assets_category` (`store_id`, `category_id`, `is_deleted`, `id`),
    KEY `idx_ita_assets_status` (`store_id`, `is_deleted`, `status`, `id`),
    KEY `idx_ita_assets_purchase` (`store_id`, `is_deleted`, `purchase_date`, `id`),
    KEY `idx_ita_assets_warranty` (`store_id`, `is_deleted`, `warranty_end`, `id`),
    KEY `idx_ita_assets_maintenance` (`store_id`, `is_deleted`, `next_maintenance_due`, `id`),
    KEY `idx_ita_assets_current_user` (`store_id`, `current_user_id`, `is_deleted`, `id`),
    KEY `idx_ita_assets_serial` (`store_id`, `serial_no`),
    CONSTRAINT `fk_ita_assets_category` FOREIGN KEY (`store_id`, `category_id`)
        REFERENCES `ita_asset_categories` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_ita_assets_department` FOREIGN KEY (`store_id`, `department_id`)
        REFERENCES `ita_departments` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `ck_ita_assets_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_assets_code` CHECK (CHAR_LENGTH(TRIM(`asset_code`)) > 0),
    CONSTRAINT `ck_ita_assets_name` CHECK (CHAR_LENGTH(TRIM(`name`)) > 0),
    CONSTRAINT `ck_ita_assets_price` CHECK (`purchase_price` IS NULL OR `purchase_price` >= 0),
    CONSTRAINT `ck_ita_assets_deleted` CHECK (`is_deleted` IN (0, 1)),
    CONSTRAINT `ck_ita_assets_retired_reason` CHECK (
        `status` <> 'retired' OR (`status_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`status_reason`)) > 0)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
