-- Initial catalogs only, for the user-confirmed store_id = 1.
-- Run after 001-004. Existing rows and all dc_ tables are preserved.
-- NOT the 300-500-asset dev/test seed. No fake users, assets or tickets.
USE `dung_infracore`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';
START TRANSACTION;

INSERT INTO `ita_asset_categories`
    (`store_id`, `code`, `name`, `is_software`, `default_lifespan_years`, `maintenance_cycle_months`)
SELECT 1, seed.code, seed.name, seed.is_software, seed.lifespan, seed.cycle
FROM (
    SELECT 'COMPUTER' AS code, 'Máy tính' AS name, 0 AS is_software, 5 AS lifespan, 6 AS cycle
    UNION ALL SELECT 'MONITOR', 'Màn hình', 0, 6, 6
    UNION ALL SELECT 'PRINTER', 'Máy in', 0, 5, 6
    UNION ALL SELECT 'NETWORK', 'Switch / Router', 0, 7, 6
    UNION ALL SELECT 'STORAGE', 'Thiết bị lưu trữ', 0, 5, 6
    UNION ALL SELECT 'SOFTWARE', 'Giấy phép phần mềm', 1, NULL, NULL
) AS seed
WHERE EXISTS (SELECT 1 FROM `dc_users` WHERE `store_id` = 1)
  AND NOT EXISTS (
      SELECT 1 FROM `ita_asset_categories` AS existing
      WHERE existing.store_id = 1 AND existing.code = seed.code
  );

INSERT INTO `ita_departments` (`store_id`, `code`, `name`)
SELECT 1, seed.code, seed.name
FROM (
    SELECT 'IT' AS code, 'Công nghệ thông tin' AS name
    UNION ALL SELECT 'FINANCE', 'Kế toán'
    UNION ALL SELECT 'HR', 'Nhân sự'
    UNION ALL SELECT 'SALES', 'Kinh doanh'
    UNION ALL SELECT 'OPERATIONS', 'Vận hành'
    UNION ALL SELECT 'EXECUTIVE', 'Ban giám đốc'
) AS seed
WHERE EXISTS (SELECT 1 FROM `dc_users` WHERE `store_id` = 1)
  AND NOT EXISTS (
      SELECT 1 FROM `ita_departments` AS existing
      WHERE existing.store_id = 1 AND existing.code = seed.code
  );

INSERT INTO `ita_settings` (`store_id`, `setting_key`, `setting_value`)
SELECT 1, seed.setting_key, seed.setting_value
FROM (
    SELECT 'license_warning_days' AS setting_key, '30' AS setting_value
    UNION ALL SELECT 'license_critical_days', '7'
    UNION ALL SELECT 'warranty_warning_days', '30'
    UNION ALL SELECT 'budget_contingency_percent', '10'
    UNION ALL SELECT 'display_timezone', 'Asia/Ho_Chi_Minh'
    UNION ALL SELECT 'currency', 'VND'
) AS seed
WHERE EXISTS (SELECT 1 FROM `dc_users` WHERE `store_id` = 1)
  AND NOT EXISTS (
      SELECT 1 FROM `ita_settings` AS existing
      WHERE existing.store_id = 1 AND existing.setting_key = seed.setting_key
  );

COMMIT;

SELECT `code`, `name`, `is_software`, `default_lifespan_years`, `maintenance_cycle_months`
FROM `dung_infracore`.`ita_asset_categories` WHERE `store_id` = 1 ORDER BY `code`;
SELECT `code`, `name` FROM `dung_infracore`.`ita_departments` WHERE `store_id` = 1 ORDER BY `code`;
SELECT `setting_key`, `setting_value` FROM `dung_infracore`.`ita_settings` WHERE `store_id` = 1 ORDER BY `setting_key`;
-- Expected on a clean catalog: 6 categories, 6 departments, 6 settings.
-- If a statement fails before COMMIT, stop and ROLLBACK in that same session.
