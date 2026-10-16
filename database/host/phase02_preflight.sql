-- READ ONLY. Run before 005_ita_auth.sql. Back up the full DB outside the repo first.
SELECT 'dung_infracore' AS expected_database, VERSION() AS server_version;
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES
WHERE TABLE_SCHEMA='dung_infracore'
AND TABLE_NAME IN ('ita_departments','ita_audit_logs','dc_users','ita_user_roles','ita_user_scopes','ita_login_limits')
ORDER BY TABLE_NAME;
-- First installation: only departments, audit_logs and dc_users should exist above.
-- If any of the three auth tables exists, stop and report; do not rerun CREATE.
SELECT id,store_id,status FROM dung_infracore.dc_users WHERE store_id=1 AND id=111;
-- Expected: one row, id=111, store_id=1, status=1. No credentials selected.
SELECT COLUMN_NAME,DATA_TYPE FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='dung_infracore' AND TABLE_NAME='dc_users'
AND COLUMN_NAME IN ('id','store_id','username','email','password','password_hash','status','last_login');
-- Expected 8 columns. No ALTER of dc_users is included in Phase 2.
