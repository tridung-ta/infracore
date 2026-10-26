"""Static migration safety checks only; no SQL parser/server/database execution."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
checks = 0


def check(condition, label):
    global checks
    if not condition:
        raise AssertionError(label)
    checks += 1


def read(path):
    data = (ROOT / path).read_bytes()
    check(not data.startswith(b'\xef\xbb\xbf'), path + ' no BOM')
    return data.decode('utf-8').replace('\r\n', '\n')


gate = read('docs/BE_GATE_13_SQL.md')
migration = read('database/migrations/006_ita_network_links.sql')
approved = gate.split('```sql', 1)[1].split('```', 1)[0]
approved = approved[approved.index('CREATE TABLE'):].strip()
actual = migration[migration.index('CREATE TABLE'):].strip()
check(actual == approved, 'Migration must match approved CREATE byte-for-byte after LF normalization')
check(migration.count('CREATE TABLE') == 1, 'Exactly one new table')
code = re.sub(r'--[^\n]*', '', migration)
check(not re.search(r'\b(DROP|ALTER|TRUNCATE|INSERT|REPLACE|UPDATE\s+ita_|DELETE\s+FROM|CREATE\s+DATABASE)\b', code, re.I), 'No seed/destructive/other schema mutation')
check(not re.search(r'\bdc_\w+', code, re.I), 'No native table references')
check('IF NOT EXISTS' not in code.upper(), 'No silent schema mismatch')
check(code.strip().startswith('USE `dung_infracore`;'), 'Explicit approved target')
check('foreign_key_checks' not in code.lower(), 'Never disable FK')
check(code.count('FOREIGN KEY') == 2, 'Two composite FKs')
check(code.count('REFERENCES `ita_assets` (`store_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT') == 2, 'Correct parent and restrict rules')
check('(`store_id`,`source_asset_id`,`target_asset_id`,`active_slot`)' in code, 'Active-pair uniqueness key')
check('(CASE WHEN `is_deleted` = 0 THEN 1 ELSE NULL END) STORED' in code, 'Unique generated slot preserves deleted history')
check('ENGINE=InnoDB' in code and 'COLLATE=utf8mb4_unicode_ci' in code, 'Approved engine/collation')
check('`source_asset_id` < `target_asset_id`' in code, 'Canonical pair CHECK')
check(code.count('DATETIME(6)') == 2, 'Microsecond timestamps')
check(len(re.findall(r'^  `[a-z_]+` ', code, re.M)) == 13, 'Thirteen columns')

for name in ['phase13_preflight.sql', 'phase13_verify.sql']:
    text = read('database/host/' + name)
    plain = re.sub(r'--[^\n]*', '', text)
    # No quoted semicolons occur in these fixed metadata queries.
    statements = [s.strip() for s in plain.split(';') if s.strip()]
    check(bool(statements), name + ' has statements')
    for statement in statements:
        check(bool(re.match(r'^(SELECT|SHOW)\b', statement, re.I)), name + ' read-only statement')
        check(not re.search(r'\b(INTO\s+(OUTFILE|DUMPFILE)|FOR\s+UPDATE|LOAD_FILE|SLEEP|BENCHMARK)\b', statement, re.I), name + ' no side effects')
    check('dung_infracore' in plain, name + ' schema explicit')

print(f'PASS: {checks} static SQL contract checks; no SQL syntax/DB/enforcement proof.')
