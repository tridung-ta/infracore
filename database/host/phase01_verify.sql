-- READ ONLY. Run after phase01_create_ita_tables.sql.
USE `dung_infracore`;
SELECT DATABASE() AS selected_database, VERSION() AS server_version;

SELECT TABLE_NAME, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (
    'ita_departments', 'ita_asset_categories', 'ita_settings', 'ita_assets',
    'ita_licenses', 'ita_license_allocations', 'ita_assignments', 'ita_tickets',
    'ita_ticket_events', 'ita_audit_logs', 'ita_budget_plans', 'ita_import_batches'
)
ORDER BY TABLE_NAME;

SELECT COUNT(*) AS created_tables,
       SUM(ENGINE = 'InnoDB') AS innodb_tables,
       SUM(TABLE_COLLATION = 'utf8mb4_unicode_ci') AS utf8mb4_tables
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (
    'ita_departments', 'ita_asset_categories', 'ita_settings', 'ita_assets',
    'ita_licenses', 'ita_license_allocations', 'ita_assignments', 'ita_tickets',
    'ita_ticket_events', 'ita_audit_logs', 'ita_budget_plans', 'ita_import_batches'
);
-- Expected: 12 / 12 / 12.

SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE() AND LEFT(TABLE_NAME, 4) = 'ita_'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
-- Expected: 11 foreign keys, all referencing ita_ tables only.
