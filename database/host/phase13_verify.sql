-- READ ONLY, AFTER user applied migration 006 successfully.
-- Missing table or metadata mismatch: stop; do not rerun CREATE or repair blindly.
SELECT DATABASE() AS selected_schema, VERSION() AS server_version;
SHOW CREATE TABLE dung_infracore.ita_network_links;

SELECT TABLE_NAME, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA='dung_infracore' AND TABLE_NAME='ita_network_links';

SELECT ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
       COLUMN_DEFAULT, EXTRA, GENERATION_EXPRESSION, DATETIME_PRECISION
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='dung_infracore' AND TABLE_NAME='ita_network_links'
ORDER BY ORDINAL_POSITION;

SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA='dung_infracore' AND TABLE_NAME='ita_network_links'
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA='dung_infracore' AND TABLE_NAME='ita_network_links'
ORDER BY CONSTRAINT_TYPE, CONSTRAINT_NAME;

SELECT CONSTRAINT_NAME, COLUMN_NAME, ORDINAL_POSITION,
       REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE CONSTRAINT_SCHEMA='dung_infracore' AND TABLE_NAME='ita_network_links'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION;

SELECT CONSTRAINT_NAME, UPDATE_RULE, DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA='dung_infracore' AND TABLE_NAME='ita_network_links'
ORDER BY CONSTRAINT_NAME;

-- Count only; do not export topology or real asset metadata into chat/Git.
SELECT COUNT(*) AS total_links,
       COALESCE(SUM(is_deleted=0),0) AS active_links,
       COALESCE(SUM(is_deleted=1),0) AS soft_deleted_links
FROM dung_infracore.ita_network_links;
