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
