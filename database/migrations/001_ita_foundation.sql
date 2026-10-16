-- F1.1 / UP. Target: MariaDB 10.6.28, dung_infracore.
-- New tables only. No legacy table changes, no seed data.
-- Stop if an ita_ table already exists; do not ignore errors.
-- DOWN: see ROLLBACK.md. Never remove a table containing application data.
USE `dung_infracore`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';

CREATE TABLE `ita_departments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `parent_id` BIGINT UNSIGNED NULL,
    `manager_user_id` INT NULL COMMENT 'Application-validated dc_users.id; no FK to MyISAM',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ita_departments_store_id` (`store_id`, `id`),
    UNIQUE KEY `uq_ita_departments_store_code` (`store_id`, `code`),
    KEY `idx_ita_departments_parent` (`store_id`, `parent_id`),
    KEY `idx_ita_departments_status` (`store_id`, `status`, `id`),
    CONSTRAINT `fk_ita_departments_parent` FOREIGN KEY (`store_id`, `parent_id`)
        REFERENCES `ita_departments` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `ck_ita_departments_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_departments_code` CHECK (CHAR_LENGTH(TRIM(`code`)) > 0),
    CONSTRAINT `ck_ita_departments_name` CHECK (CHAR_LENGTH(TRIM(`name`)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ita_asset_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `is_software` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `default_lifespan_years` DECIMAL(5,2) NULL,
    `maintenance_cycle_months` SMALLINT UNSIGNED NULL,
    `default_replacement_cost` DECIMAL(18,2) NULL COMMENT 'VND',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ita_categories_store_id` (`store_id`, `id`),
    UNIQUE KEY `uq_ita_categories_store_code` (`store_id`, `code`),
    KEY `idx_ita_categories_status` (`store_id`, `status`, `id`),
    CONSTRAINT `ck_ita_categories_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_categories_code` CHECK (CHAR_LENGTH(TRIM(`code`)) > 0),
    CONSTRAINT `ck_ita_categories_name` CHECK (CHAR_LENGTH(TRIM(`name`)) > 0),
    CONSTRAINT `ck_ita_categories_software` CHECK (`is_software` IN (0, 1)),
    CONSTRAINT `ck_ita_categories_lifespan` CHECK (`default_lifespan_years` IS NULL OR `default_lifespan_years` > 0),
    CONSTRAINT `ck_ita_categories_cycle` CHECK (`maintenance_cycle_months` IS NULL OR `maintenance_cycle_months` > 0),
    CONSTRAINT `ck_ita_categories_cost` CHECK (`default_replacement_cost` IS NULL OR `default_replacement_cost` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ita_settings` (
    `store_id` INT NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` LONGTEXT NOT NULL,
    `updated_by` INT NULL COMMENT 'Application-validated dc_users.id',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`store_id`, `setting_key`),
    CONSTRAINT `ck_ita_settings_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_settings_key` CHECK (CHAR_LENGTH(TRIM(`setting_key`)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
