"""Synthetic ZIPs in memory only. No application ZIP, extraction, DB or host access."""
from pathlib import Path
import io
import json
import stat
import sys
import zipfile
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
from ita_release_validation import digest, validate_inventory, validate_path, validate_zip, checksums

checks = 0
data = b'public runtime bytes'
sha = digest(data)


def check(ok, label):
    global checks
    if not ok:
        raise AssertionError(label)
    checks += 1


def rejected(work, label):
    try:
        work()
    except (ValueError, zipfile.BadZipFile, KeyError):
        check(True, label)
    else:
        raise AssertionError('Expected rejection: ' + label)


for path in ['tmp/x', 'runtime/TMP/Host-Inventory/listing.txt', 'runtime/tests/t.php', 'handoff/artifacts/a',
             'runtime/.GIT/HEAD', '.playwright-cli/a', 'runtime/scripts/x.php', 'runtime/assets/ita/MOCK/x.json',
             'runtime/assets/ita/preview/a', 'nested/Dashboard.HTML', 'handoff/reports.html',
             'runtime/other/login-preview.html', 'runtime/.ITA-LEGACY-ENABLED', 'handoff/.ita-legacy-enabled',
             'runtime/.htaccess', 'runtime/.user.ini', 'runtime/.ita-enabled', 'runtime/.env', 'handoff/.env.prod',
             'runtime/includes/ita_database.local.php', 'handoff/dump.sql', 'runtime/key.pem', 'runtime/backup.bak',
             'runtime/uploads/a.PHP', 'runtime/staging/sub/a.php', '/absolute', 'C:/drive', '../a',
             'runtime/../a', 'runtime/./a', 'runtime//a', 'runtime/a\\b', 'runtime/a\x00b', 'runtime/a.']:
    rejected(lambda: validate_path(path, sha, {path: sha} if 'legacy-enabled' in path.lower() else None), path)
for path in ['runtime/templates/dashboard.tpl.html', 'runtime/assets/ita/js/reports.js', 'runtime/classes/services/a.php']:
    validate_path(path, sha)
    check(True, path)
for path in ['manual-review/.htaccess', 'manual-review/.user.ini', 'runtime/.ita-enabled', 'handoff/sql/006.sql']:
    validate_path(path, sha, {path: sha})
    check(True, path + ' exact exception')
    rejected(lambda: validate_path(path, '0'*64, {path: sha}), path + ' wrong bytes')
    rejected(lambda: validate_path('other/' + path, sha, {path: sha}), path + ' wrong path')

item = {'path': 'runtime/app.php', 'size_bytes': len(data), 'sha256': sha, 'origin': 'QA', 'version_or_ref': 'QA'}
manifest = {'source_commit': 'a'*40, 'files': [item]}


def bundle(payload=None, claimed=None, sums=None, extra=None):
    memory = io.BytesIO()
    with zipfile.ZipFile(memory, 'w', zipfile.ZIP_STORED) as z:
        z.writestr('runtime/app.php', data if payload is None else payload)
        z.writestr('MANIFEST.json', json.dumps(manifest if claimed is None else claimed))
        z.writestr('SHA256SUMS.txt', checksums([item]) if sums is None else sums)
        if extra:
            for name, content in extra:
                z.writestr(name, content)
    memory.seek(0)
    return memory


check(validate_zip(bundle(), manifest)['files'] == 1, 'valid synthetic archive')
rejected(lambda: validate_zip(bundle(payload=b'corrupt content'), manifest), 'payload hash/size')
rejected(lambda: validate_zip(bundle(claimed={'source_commit': 'b'*40, 'files': [item]}), manifest), 'wrong commit')
rejected(lambda: validate_zip(bundle(claimed={'source_commit': 'a'*40, 'files': []}), manifest), 'manifest omissions')
rejected(lambda: validate_zip(bundle(sums=b'wrong'), manifest), 'checksum mismatch')
for path in ['runtime/TMP/x', 'runtime/.ita-legacy-enabled', 'runtime/APP.php', 'runtime/extra.php', 'runtime/tests/']:
    rejected(lambda: validate_zip(bundle(extra=[(path, data)]), manifest), 'extra/duplicate/path ' + path)
link = zipfile.ZipInfo('runtime/link.php')
link.create_system = 3
link.external_attr = (stat.S_IFLNK | 0o777) << 16
rejected(lambda: validate_zip(bundle(extra=[(link, b'target')]), manifest), 'symlink')
raw = bytearray(bundle().getvalue())
offset = raw.index(data)
raw[offset] ^= 1
rejected(lambda: validate_zip(io.BytesIO(raw), manifest), 'CRC corruption')
rejected(lambda: validate_inventory([item, dict(item, path='runtime/APP.php')]), 'inventory duplicate case')
rejected(lambda: validate_inventory([dict(item, sha256='wrong')]), 'invalid metadata')
rejected(lambda: validate_inventory([]), 'empty inventory')
memory = io.BytesIO()
with zipfile.ZipFile(memory, 'w') as archive:
    archive.writestr('runtime/app.phpXjunk', data)
    archive.writestr('MANIFEST.json', json.dumps(manifest))
    archive.writestr('SHA256SUMS.txt', checksums([item]))
nul_name = memory.getvalue().replace(b'app.phpXjunk', b'app.php\x00junk')
rejected(lambda: validate_zip(io.BytesIO(nul_name), manifest), 'NUL filename silently truncated by ZipInfo')
reparse = zipfile.ZipInfo('runtime/reparse.php')
reparse.external_attr = 0x400
rejected(lambda: validate_zip(bundle(extra=[(reparse, data)]), manifest), 'Windows reparse flag')
print(f'PASS: {checks} release validation checks; synthetic in-memory ZIP only, no real release created.')
