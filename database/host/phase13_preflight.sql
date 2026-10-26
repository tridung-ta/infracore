-- READ ONLY. User runs manually only on the intended project schema.
-- Expected schema: dung_infracore. Stop if target/backup/source is unconfirmed.
SELECT DATABASE() AS selected_schema, VERSION() AS server_version;

SELECT TABLE_NAME, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA='dung_infracore'
  AND TABLE_NAME IN ('ita_assets','ita_network_links');

SELECT COUNT(*) AS network_table_exists
FROM information_schema.TABLES
WHERE TABLE_SCHEMA='dung_infracore' AND TABLE_NAME='ita_network_links';

SHOW CREATE TABLE dung_infracore.ita_assets;

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='dung_infracore' AND TABLE_NAME='ita_assets'
  AND COLUMN_NAME IN ('id','store_id') ORDER BY ORDINAL_POSITION;

SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA='dung_infracore' AND TABLE_NAME='ita_assets'
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- No proposed constraint name may already belong to another table.
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA='dung_infracore'
  AND CONSTRAINT_NAME IN ('fk_ita_network_source','fk_ita_network_target',
      'ck_ita_network_store','ck_ita_network_pair','ck_ita_network_deleted');

-- This describes this SQL-tool session, not the PHP application's connection.
SHOW GRANTS FOR CURRENT_USER();
