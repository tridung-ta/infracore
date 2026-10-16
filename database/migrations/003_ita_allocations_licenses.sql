-- F1.3 / UP. Run after 001 and 002. DOWN: see ROLLBACK.md.
USE `dung_infracore`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';

CREATE TABLE `ita_licenses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `software_name` VARCHAR(200) NOT NULL,
    `vendor` VARCHAR(200) NULL,
    `license_key_enc` TEXT NULL COMMENT 'Authenticated ciphertext only; never plaintext',
    `license_type` ENUM('per_device', 'per_user', 'per_department') NOT NULL,
    `total_seats` INT UNSIGNED NOT NULL,
    `start_date` DATE NULL,
    `expire_date` DATE NULL,
    `cost` DECIMAL(18,2) NULL COMMENT 'VND',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_by` INT NOT NULL COMMENT 'Application-validated dc_users.id',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ita_licenses_store_id` (`store_id`, `id`),
    KEY `idx_ita_licenses_expiry` (`store_id`, `status`, `expire_date`, `id`),
    CONSTRAINT `ck_ita_licenses_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_licenses_name` CHECK (CHAR_LENGTH(TRIM(`software_name`)) > 0),
    CONSTRAINT `ck_ita_licenses_seats` CHECK (`total_seats` > 0),
    CONSTRAINT `ck_ita_licenses_cost` CHECK (`cost` IS NULL OR `cost` >= 0),
    CONSTRAINT `ck_ita_licenses_dates` CHECK (`start_date` IS NULL OR `expire_date` IS NULL OR `expire_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ita_license_allocations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `license_id` BIGINT UNSIGNED NOT NULL,
    `asset_id` BIGINT UNSIGNED NULL,
    `user_id` INT NULL COMMENT 'Application-validated dc_users.id',
    `department_id` BIGINT UNSIGNED NULL,
    `allocated_by` INT NOT NULL COMMENT 'Application-validated dc_users.id',
    `allocated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `released_at` DATETIME NULL,
    `active_asset_id` BIGINT UNSIGNED GENERATED ALWAYS AS (IF(`released_at` IS NULL, `asset_id`, NULL)) STORED,
    `active_user_id` INT GENERATED ALWAYS AS (IF(`released_at` IS NULL, `user_id`, NULL)) STORED,
    `active_department_id` BIGINT UNSIGNED GENERATED ALWAYS AS (IF(`released_at` IS NULL, `department_id`, NULL)) STORED,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ita_license_active_asset` (`store_id`, `license_id`, `active_asset_id`),
    UNIQUE KEY `uq_ita_license_active_user` (`store_id`, `license_id`, `active_user_id`),
    UNIQUE KEY `uq_ita_license_active_department` (`store_id`, `license_id`, `active_department_id`),
    KEY `idx_ita_license_allocations_license` (`store_id`, `license_id`, `released_at`),
    KEY `idx_ita_license_allocations_asset` (`store_id`, `asset_id`, `released_at`),
    KEY `idx_ita_license_allocations_user` (`store_id`, `user_id`, `released_at`),
    KEY `idx_ita_license_allocations_department` (`store_id`, `department_id`, `released_at`),
    CONSTRAINT `fk_ita_allocations_license` FOREIGN KEY (`store_id`, `license_id`)
        REFERENCES `ita_licenses` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_ita_allocations_asset` FOREIGN KEY (`store_id`, `asset_id`)
        REFERENCES `ita_assets` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_ita_allocations_department` FOREIGN KEY (`store_id`, `department_id`)
        REFERENCES `ita_departments` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `ck_ita_allocations_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_allocations_target` CHECK (
        (`asset_id` IS NOT NULL) + (`user_id` IS NOT NULL) + (`department_id` IS NOT NULL) = 1
    ),
    CONSTRAINT `ck_ita_allocations_dates` CHECK (`released_at` IS NULL OR `released_at` >= `allocated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ita_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `asset_id` BIGINT UNSIGNED NOT NULL,
    `to_user_id` INT NULL COMMENT 'Application-validated dc_users.id',
    `to_department_id` BIGINT UNSIGNED NULL,
    `assigned_by` INT NOT NULL COMMENT 'Application-validated dc_users.id',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `due_return` DATE NULL,
    `returned_at` DATETIME NULL,
    `handover_no` VARCHAR(64) NOT NULL,
    `handover_file` VARCHAR(255) NULL COMMENT 'Relative private storage identifier, not a public URL',
    `acknowledged_by` INT NULL COMMENT 'Application-validated dc_users.id',
    `acknowledged_at` DATETIME NULL,
    `note` TEXT NULL,
    `active_asset_id` BIGINT UNSIGNED GENERATED ALWAYS AS (IF(`returned_at` IS NULL, `asset_id`, NULL)) STORED,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ita_assignments_handover` (`store_id`, `handover_no`),
    UNIQUE KEY `uq_ita_assignments_active_asset` (`store_id`, `active_asset_id`),
    KEY `idx_ita_assignments_asset` (`store_id`, `asset_id`, `returned_at`, `assigned_at`),
    KEY `idx_ita_assignments_department` (`store_id`, `to_department_id`, `returned_at`),
    KEY `idx_ita_assignments_user` (`store_id`, `to_user_id`, `returned_at`),
    KEY `idx_ita_assignments_due` (`store_id`, `returned_at`, `due_return`),
    CONSTRAINT `fk_ita_assignments_asset` FOREIGN KEY (`store_id`, `asset_id`)
        REFERENCES `ita_assets` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `fk_ita_assignments_department` FOREIGN KEY (`store_id`, `to_department_id`)
        REFERENCES `ita_departments` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `ck_ita_assignments_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_assignments_target` CHECK ((`to_user_id` IS NOT NULL) + (`to_department_id` IS NOT NULL) = 1),
    CONSTRAINT `ck_ita_assignments_handover` CHECK (CHAR_LENGTH(TRIM(`handover_no`)) > 0),
    CONSTRAINT `ck_ita_assignments_return` CHECK (`returned_at` IS NULL OR `returned_at` >= `assigned_at`),
    CONSTRAINT `ck_ita_assignments_due` CHECK (`due_return` IS NULL OR `due_return` >= DATE(`assigned_at`)),
    CONSTRAINT `ck_ita_assignments_ack` CHECK (
        (`acknowledged_by` IS NULL AND `acknowledged_at` IS NULL)
        OR (`acknowledged_by` IS NOT NULL AND `acknowledged_at` IS NOT NULL AND `acknowledged_at` >= `assigned_at`)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
