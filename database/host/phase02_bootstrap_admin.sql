-- User selected existing account 111 for store 1. Run after 005_ita_auth.sql.
-- First-admin bootstrap only. Does not change credentials or overwrite mappings.
USE `dung_infracore`;
INSERT INTO `ita_user_roles` (`store_id`,`user_id`,`role`,`enabled`,`updated_by`)
SELECT 1, u.id, 'admin_it', 1, NULL
FROM `dc_users` AS u
WHERE u.store_id=1 AND u.id=111 AND u.status=1
  AND NOT EXISTS (SELECT 1 FROM `ita_user_roles` r WHERE r.store_id=1 AND r.role='admin_it' AND r.enabled=1)
  AND NOT EXISTS (SELECT 1 FROM `ita_user_roles` r WHERE r.store_id=1 AND r.user_id=111);
SELECT store_id,user_id,role,enabled FROM `ita_user_roles` WHERE store_id=1 AND user_id=111;
-- Expected exactly one enabled admin_it row. If empty, report the result; do not change dc_users status.
