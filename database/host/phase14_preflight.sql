-- READ ONLY. Select the intended database in phpMyAdmin first.
SELECT DATABASE() AS selected_schema, VERSION() AS server_version;
SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN
('dc_users','ita_user_roles','ita_user_scopes','ita_departments','ita_audit_logs','ita_user_permissions');
SHOW COLUMNS FROM ita_user_roles;
SELECT store_id,role,enabled,COUNT(*) AS users_count FROM ita_user_roles GROUP BY store_id,role,enabled;
SELECT r.store_id,COUNT(*) AS active_native_admins FROM ita_user_roles r
JOIN dc_users u ON u.store_id=r.store_id AND u.id=r.user_id
WHERE r.role='admin_it' AND r.enabled=1 AND u.status=1 GROUP BY r.store_id;
-- No passwords, hashes, config or personal identity fields are selected.
-- Existing permissions table: stop CREATE/backfill and inspect, never overwrite it.
