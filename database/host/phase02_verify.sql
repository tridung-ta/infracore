-- READ ONLY. Run after 005_ita_auth.sql and phase02_bootstrap_admin.sql.
SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES
WHERE TABLE_SCHEMA='dung_infracore'
AND TABLE_NAME IN ('ita_user_roles','ita_user_scopes','ita_login_limits') ORDER BY TABLE_NAME;
-- Expected 3 rows, all InnoDB / utf8mb4_unicode_ci.
SELECT CONSTRAINT_NAME,CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA='dung_infracore' AND TABLE_NAME='ita_user_scopes';
-- Must include fk_ita_scopes_department FOREIGN KEY.
SELECT r.store_id,r.user_id,r.role,r.enabled,u.status AS native_status
FROM dung_infracore.ita_user_roles r JOIN dung_infracore.dc_users u
ON u.store_id=r.store_id AND u.id=r.user_id WHERE r.store_id=1 AND r.user_id=111;
-- Expected exactly one row: 1 / 111 / admin_it / 1 / 1.
