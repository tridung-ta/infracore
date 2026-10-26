-- F13.1 / SQL gate 13 approved by user 2026-10-26. NOT applied by agent.
-- User: confirm target + source/DB backup and review phase13_preflight.sql first.
-- Additive UP only. No seed or automatic DOWN. See PHASE13_SQL_HOST_GUIDE.md.
USE `dung_infracore`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';

CREATE TABLE `ita_network_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT NOT NULL,
  `source_asset_id` BIGINT UNSIGNED NOT NULL,
  `target_asset_id` BIGINT UNSIGNED NOT NULL,
  `link_type` ENUM('ethernet','fiber','wireless') NOT NULL,
  `source_port` VARCHAR(64) NOT NULL DEFAULT '',
  `target_port` VARCHAR(64) NOT NULL DEFAULT '',
  `capacity_label` VARCHAR(64) NOT NULL DEFAULT '',
  `note` TEXT NOT NULL,
  `is_deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `active_slot` TINYINT GENERATED ALWAYS AS
      (CASE WHEN `is_deleted` = 0 THEN 1 ELSE NULL END) STORED,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
      ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ita_network_pair` (`store_id`,`source_asset_id`,`target_asset_id`,`active_slot`),
  KEY `idx_ita_network_list` (`store_id`,`is_deleted`,`id`),
  KEY `idx_ita_network_target` (`store_id`,`target_asset_id`),
  CONSTRAINT `fk_ita_network_source` FOREIGN KEY (`store_id`,`source_asset_id`)
      REFERENCES `ita_assets` (`store_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ita_network_target` FOREIGN KEY (`store_id`,`target_asset_id`)
      REFERENCES `ita_assets` (`store_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_ita_network_store` CHECK (`store_id` > 0),
  CONSTRAINT `ck_ita_network_pair` CHECK (`source_asset_id` < `target_asset_id`),
  CONSTRAINT `ck_ita_network_deleted` CHECK (`is_deleted` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
