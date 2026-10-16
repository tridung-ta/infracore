-- READ ONLY. Run first in phpMyAdmin. Expected selected_database: dung_infracore.
USE `dung_infracore`;
SELECT DATABASE() AS selected_database, VERSION() AS server_version;
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'dung_infracore' AND LEFT(TABLE_NAME, 4) = 'ita_'
ORDER BY TABLE_NAME;
-- The second result must be EMPTY for this first installation.
-- If any ita_ table exists, stop and send the result to Codex.
