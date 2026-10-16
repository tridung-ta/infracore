-- Phase 2: new ITA access mappings and shared login limiter only.
-- No modification of dc_ schema or users. Back up before applying on host.
USE `dung_infracore`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `ita_user_roles` (
    `store_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('admin_it','system_manager','support_technician') NOT NULL,
    `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `auth_revision` INT UNSIGNED NOT NULL DEFAULT 1,
    `updated_by` INT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`store_id`,`user_id`),
    KEY `idx_ita_roles_admin` (`store_id`,`role`,`enabled`),
    CONSTRAINT `ck_ita_roles_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_roles_enabled` CHECK (`enabled` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `ita_user_scopes` (
    `store_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`store_id`,`user_id`,`department_id`),
    KEY `idx_ita_scopes_department` (`store_id`,`department_id`,`active`),
    CONSTRAINT `fk_ita_scopes_department` FOREIGN KEY (`store_id`,`department_id`)
        REFERENCES `ita_departments` (`store_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `ck_ita_scopes_store` CHECK (`store_id` > 0),
    CONSTRAINT `ck_ita_scopes_active` CHECK (`active` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `ita_login_limits` (
    `store_id` INT NOT NULL,
    `bucket_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `window_started` DATETIME NOT NULL,
    `last_attempt` DATETIME NOT NULL,
    PRIMARY KEY (`store_id`,`bucket_hash`),
    KEY `idx_ita_limits_expiry` (`last_attempt`),
    CONSTRAINT `ck_ita_limits_store` CHECK (`store_id` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- DOWN: keep inactive tables on hosting; review backup, writers and data first.
-- Never drop access mappings or login records to work around an application error.
