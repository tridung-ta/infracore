-- MANUAL ONLY, maintenance window: stop access/role edits and application writes first.
-- Use one SQL connection for the entire block; select the correct database in phpMyAdmin.
-- Fill all three values. NULL means no permission rows will be inserted.
SET @expected_schema = NULL; -- exact database name, quoted
SET @store_id = NULL; -- exact positive store ID
SET @operator_id = NULL; -- existing active ITA admin user ID in that store
START TRANSACTION;
-- Lock the same role rows as the capability writer. Native MyISAM is NOT protected by this lock.
SELECT user_id,role,enabled,auth_revision FROM ita_user_roles
WHERE store_id=@store_id ORDER BY user_id FOR UPDATE;
SET @ok = (
 DATABASE()=@expected_schema AND @store_id>0 AND @operator_id>0
 AND (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
   AND TABLE_NAME IN ('ita_user_permissions','ita_user_roles') AND ENGINE='InnoDB')=2
 AND (SELECT COUNT(*) FROM ita_user_permissions WHERE store_id=@store_id)=0
 AND EXISTS (SELECT 1 FROM ita_user_roles r JOIN dc_users u ON u.store_id=r.store_id AND u.id=r.user_id
   WHERE r.store_id=@store_id AND r.user_id=@operator_id AND r.enabled=1 AND r.role='admin_it' AND u.status=1)
 AND NOT EXISTS (SELECT 1 FROM ita_user_roles r LEFT JOIN dc_users u ON u.store_id=r.store_id AND u.id=r.user_id
   WHERE r.store_id=@store_id AND r.enabled=1 AND (u.id IS NULL OR u.status<>1 OR r.auth_revision>=4294967295
     OR r.role NOT IN ('admin_it','system_manager','support_technician')
     OR (r.role<>'admin_it' AND NOT EXISTS (SELECT 1 FROM ita_user_scopes s JOIN ita_departments d
       ON d.store_id=s.store_id AND d.id=s.department_id
       WHERE s.store_id=r.store_id AND s.user_id=r.user_id AND s.active=1 AND d.status='active'))))
);
SELECT DATABASE() AS selected_schema, @store_id AS target_store, @ok AS ready_must_equal_1;
-- If ready is not 1: ROLLBACK and stop. Never bypass a failed guard.
INSERT INTO ita_user_permissions (store_id,user_id,permission_key,active,updated_by)
SELECT r.store_id,r.user_id,p.permission_key,1,@operator_id
FROM ita_user_roles r JOIN (
SELECT 'admin_it' AS role, 'asset.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'asset.create' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'asset.update' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'asset.status' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'asset.note' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'asset.retire' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'asset.delete' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'category.manage' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'import.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'import.upload' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'import.confirm' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'asset.export' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'assignment.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'assignment.create' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'assignment.return' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'assignment.acknowledge' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'assignment.pdf' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'license.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'license.manage' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'license.allocate' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'license.allocate_user' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'license.overage' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'license.reveal' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'ticket.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'ticket.create' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'ticket.assign' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'ticket.process' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'ticket.cost' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'ticket.ready' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'ticket.statistics' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'ticket.statistics_self' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'dashboard.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'alert.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'lifecycle.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'forecast.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'forecast.configure' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'forecast.plan' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'forecast.export' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'report.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'report.maintenance' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'report.export' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'network.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'network.manage' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'audit.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'access.read' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'access.manage' AS permission_key
UNION ALL
SELECT 'admin_it' AS role, 'scope.store' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'asset.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'asset.status' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'asset.note' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'assignment.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'assignment.return' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'assignment.acknowledge' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'assignment.pdf' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'ticket.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'ticket.create' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'asset.create' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'asset.update' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'import.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'import.upload' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'import.confirm' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'asset.export' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'assignment.create' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'license.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'license.allocate' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'ticket.assign' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'ticket.statistics' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'dashboard.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'alert.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'lifecycle.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'forecast.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'forecast.plan' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'forecast.export' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'report.read' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'report.export' AS permission_key
UNION ALL
SELECT 'system_manager' AS role, 'network.read' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'asset.read' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'asset.status' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'asset.note' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'assignment.read' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'assignment.return' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'assignment.acknowledge' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'assignment.pdf' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'ticket.read' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'ticket.create' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'ticket.process' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'ticket.statistics_self' AS permission_key
UNION ALL
SELECT 'support_technician' AS role, 'report.maintenance' AS permission_key
) p ON p.role=r.role
WHERE r.store_id=@store_id AND r.enabled=1 AND @ok=1;
SELECT COUNT(*) INTO @inserted FROM ita_user_permissions WHERE store_id=@store_id;
UPDATE ita_user_roles r SET r.auth_revision=r.auth_revision+1,r.updated_by=@operator_id
WHERE r.store_id=@store_id AND r.enabled=1 AND @ok=1 AND @inserted>0;
SELECT @ok AS ready_must_equal_1,@inserted AS inserted_permission_rows;
SELECT r.user_id,r.role,COUNT(p.permission_key) AS permission_count
FROM ita_user_roles r LEFT JOIN ita_user_permissions p ON p.store_id=r.store_id AND p.user_id=r.user_id AND p.active=1
WHERE r.store_id=@store_id AND r.enabled=1 GROUP BY r.user_id,r.role ORDER BY r.user_id;
-- DEFAULT DRY RUN: all backfill and revision changes above are rolled back.
ROLLBACK;
-- After a successful dry run, review counts and rerun the ENTIRE block with only
-- the final ROLLBACK replaced by COMMIT. Any SQL error: ROLLBACK, STOP, investigate.
-- Never append COMMIT after this ROLLBACK or run only part of the block.
