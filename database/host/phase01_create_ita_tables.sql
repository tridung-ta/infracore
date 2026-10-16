-- InfraCore Phase 1 / 12 new ITA tables / MariaDB 10.6.28
-- Read README.md. Backup schema + data first. Run preflight first.
-- No legacy table changes. No seed. Run ONCE; stop on any error.
-- DDL is not transactionally rolled back as a whole.

-- ===== 001_ita_foundation.sql =====
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


-- ===== 002_ita_assets.sql =====
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


-- ===== 003_ita_allocations_licenses.sql =====
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


-- ===== 004_ita_operations.sql =====
-- F1.4 / UP. Run after 001-003. DOWN: see ROLLBACK.md.
USE `dung_infracore`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';

CREATE TABLE `ita_tickets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `ticket_no` VARCHAR(64) NOT NULL,
    `asset_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('repair', 'periodic') NOT NULL DEFAULT 'repair',
    `reported_by` INT NOT NULL COMMENT 'Application-validated dc_users.id',
    `technician_id` INT NULL COMMENT 'Application-validated dc_users.id',
    `issue` TEXT NOT NULL,
    `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `status` ENUM('pending', 'resolved', 'failed') NOT NULL DEFAULT 'pending',
    `opened_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `handled_date` DATE NULL,
    `resolved_at` DATETIME NULL,
    `cost` DECIMAL(18,2) NOT NULL DEFAULT 0 COMMENT 'VND',
    `resolution_note` TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ita_tickets_store_id` (`store_id`, `id`),
    UNIQUE KEY `uq_ita_tickets_number` (`store_id`, `ticket_no`),
    KEY `idx_ita_tickets_asset_status` (`store_id`, `asset_id`, `status`, `opened_at`),
    KEY `idx_ita_tickets_technician` (`store_id`, `technician_id`, `status`, `opened_at`),
    KEY `idx_ita_tickets_opened` (`store_id`, `opened_at`, `id`),
    KEY `idx_ita_tickets_status` (`store_id`, `status`, `opened_at`, `id`),
    CONSTRAINT `fk_ita_tickets_asset` FOREIGN KEY (`store_id`, `asset_id`)
        REFERENCES `ita_assets` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `ck_ita_tickets_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_tickets_number` CHECK (CHAR_LENGTH(TRIM(`ticket_no`)) > 0),
    CONSTRAINT `ck_ita_tickets_issue` CHECK (CHAR_LENGTH(TRIM(`issue`)) > 0),
    CONSTRAINT `ck_ita_tickets_cost` CHECK (`cost` >= 0),
    CONSTRAINT `ck_ita_tickets_resolved` CHECK (`resolved_at` IS NULL OR `resolved_at` >= `opened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ita_ticket_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `ticket_id` BIGINT UNSIGNED NOT NULL,
    `from_status` ENUM('pending', 'resolved', 'failed') NULL,
    `to_status` ENUM('pending', 'resolved', 'failed') NOT NULL,
    `user_id` INT NOT NULL COMMENT 'Application-validated dc_users.id',
    `note` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ita_events_ticket` (`store_id`, `ticket_id`, `created_at`, `id`),
    CONSTRAINT `fk_ita_events_ticket` FOREIGN KEY (`store_id`, `ticket_id`)
        REFERENCES `ita_tickets` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `ck_ita_events_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_events_transition` CHECK (
        (`from_status` IS NULL AND `to_status` = 'pending')
        OR (`from_status` IS NOT NULL AND `from_status` = 'pending' AND `to_status` IN ('resolved', 'failed'))
        OR (`from_status` IS NOT NULL AND `from_status` = 'failed' AND `to_status` = 'pending')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ita_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `user_id` INT NOT NULL COMMENT 'Application-validated dc_users.id; no IP collected',
    `action` VARCHAR(64) NOT NULL,
    `entity_type` VARCHAR(64) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `before_json` LONGTEXT NULL,
    `after_json` LONGTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ita_audit_user` (`store_id`, `user_id`, `created_at`, `id`),
    KEY `idx_ita_audit_entity` (`store_id`, `entity_type`, `entity_id`, `created_at`),
    KEY `idx_ita_audit_created` (`store_id`, `created_at`, `id`),
    CONSTRAINT `ck_ita_audit_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_audit_action` CHECK (CHAR_LENGTH(TRIM(`action`)) > 0),
    CONSTRAINT `ck_ita_audit_entity` CHECK (CHAR_LENGTH(TRIM(`entity_type`)) > 0),
    CONSTRAINT `ck_ita_audit_before_json` CHECK (`before_json` IS NULL OR JSON_VALID(`before_json`)),
    CONSTRAINT `ck_ita_audit_after_json` CHECK (`after_json` IS NULL OR JSON_VALID(`after_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ita_budget_plans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `year` SMALLINT UNSIGNED NOT NULL,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `planned_headcount_increase` INT UNSIGNED NOT NULL DEFAULT 0,
    `note` TEXT NULL,
    `status` ENUM('draft', 'finalized') NOT NULL DEFAULT 'draft',
    `calculation_snapshot_json` LONGTEXT NULL COMMENT 'Immutable inputs/results when finalized; application enforces freeze',
    `finalized_at` DATETIME NULL,
    `created_by` INT NOT NULL COMMENT 'Application-validated dc_users.id',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ita_budget_department_year` (`store_id`, `year`, `department_id`),
    KEY `idx_ita_budget_department` (`store_id`, `department_id`, `year`),
    CONSTRAINT `fk_ita_budget_department` FOREIGN KEY (`store_id`, `department_id`)
        REFERENCES `ita_departments` (`store_id`, `id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `ck_ita_budget_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_budget_year` CHECK (`year` BETWEEN 2000 AND 2200),
    CONSTRAINT `ck_ita_budget_snapshot` CHECK (`calculation_snapshot_json` IS NULL OR JSON_VALID(`calculation_snapshot_json`)),
    CONSTRAINT `ck_ita_budget_finalized` CHECK (
        (`status` = 'draft' AND `finalized_at` IS NULL)
        OR (`status` = 'finalized' AND `finalized_at` IS NOT NULL AND `calculation_snapshot_json` IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ita_import_batches` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT NOT NULL,
    `user_id` INT NOT NULL COMMENT 'Application-validated dc_users.id',
    `filename` VARCHAR(255) NOT NULL COMMENT 'Display name only; never execute as a path',
    `staging_file` VARCHAR(255) NULL COMMENT 'Relative private storage identifier',
    `total_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `ok_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `error_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('uploaded', 'validated', 'importing', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'uploaded',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expires_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ita_imports_user` (`store_id`, `user_id`, `created_at`, `id`),
    KEY `idx_ita_imports_status` (`store_id`, `status`, `created_at`, `id`),
    CONSTRAINT `ck_ita_imports_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_imports_rows` CHECK (`ok_rows` + `error_rows` <= `total_rows`),
    CONSTRAINT `ck_ita_imports_expiry` CHECK (`expires_at` IS NULL OR `expires_at` >= `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
