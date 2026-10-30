-- READ ONLY, after manual creation/backfill in the intended schema.
SELECT DATABASE() AS selected_schema;
SHOW CREATE TABLE ita_user_permissions;
SELECT r.store_id,r.user_id,r.role,r.enabled,r.auth_revision,COUNT(p.permission_key) AS active_permissions
FROM ita_user_roles r LEFT JOIN ita_user_permissions p
ON p.store_id=r.store_id AND p.user_id=r.user_id AND p.active=1
GROUP BY r.store_id,r.user_id,r.role,r.enabled,r.auth_revision ORDER BY r.store_id,r.user_id;
-- At INITIAL mapping only: enabled Admin=47, Manager=29, Support=12.
-- After custom edits counts intentionally differ; do not rerun backfill to reset them.
