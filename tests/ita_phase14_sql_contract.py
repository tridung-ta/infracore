"""Static SQL artifact checks only. Runs pure PHP preset export, never connects DB."""
from pathlib import Path
import json,re,subprocess
root=Path(__file__).resolve().parents[1]
php=r'D:\Hoc_PHP\php\php-8.5.10-nts-Win32-vs17-x64\php.exe'
code="require 'classes/security/itapermissions.class.php'; echo json_encode(array_combine(['admin_it','system_manager','support_technician'],array_map(['ItaPermissions','preset'],['admin_it','system_manager','support_technician'])));"
presets=json.loads(subprocess.check_output([php,'-r',code],cwd=root))
sql=(root/'database/host/phase14_permissions_backfill.sql').read_text(encoding='utf-8')
ddl=(root/'database/migrations/007_ita_user_permissions.sql').read_text(encoding='utf-8')
pairs=re.findall(r"SELECT '([^']+)' AS role, '([^']+)' AS permission_key",sql)
expected={(role,key) for role,keys in presets.items() for key in keys}
assert len(pairs)==len(set(pairs)) and set(pairs)==expected
assert all(f'SET @{name} = NULL;' in sql for name in ['expected_schema','store_id','operator_id'])
assert "AND ENGINE='InnoDB')=2" in sql
assert "TABLE_NAME IN ('ita_user_permissions','ita_user_roles')" in sql
assert 'START TRANSACTION;' in sql and '\nROLLBACK;' in sql
assert 'FOR UPDATE' in sql and 'AND @ok=1' in sql
assert 'r.auth_revision>=4294967295' in sql
assert 'UPDATE dc_' not in sql and 'DELETE FROM' not in sql and 'REPLACE INTO' not in sql
assert 'IF NOT EXISTS' not in ddl and 'ENGINE=InnoDB' in ddl
assert set(re.findall(r'CREATE TABLE (\w+)',ddl))=={'ita_user_permissions'}
assert 'USE `' not in sql+ddl
print(f'SQL007 static contract passed: {len(expected)} preset pairs, no SQL executed.')
