"""Create a fixed, hashed Phase 3 delta; never reads private config or databases."""
from pathlib import Path
from hashlib import sha256
from zipfile import ZipFile, ZIP_DEFLATED
import json

ROOT = Path(__file__).resolve().parents[1]
RUNTIME = [
    'assets/ita/css/ita-shell.css',
    'assets/ita/js/ita-admin.js',
    'assets/ita/js/ita-shell.js',
    'assets/ita/js/ita-assets.js',
    'classes/dao/itaassetobjects.class.php',
    'classes/services/itaapplication.class.php',
    'classes/services/itaauditservice.class.php',
    'classes/services/itaassetvalidation.class.php',
    'classes/services/itaassetservice.class.php',
    'classes/services/itacategoryservice.class.php',
    'includes/ita_bootstrap.inc.php',
    'includes/ita_mode.inc.php',
    'templates/admin/ita/layout.tpl.html',
    'templates/admin/ita/partials/asseteditor.tpl.html',
]
DOCS = ['docs/PHASE03_HOST_TEST.md', 'docs/PHASE03_VERIFY.md']
payload = {}
for relative in RUNTIME + DOCS:
    source = ROOT / relative
    if source.is_symlink() or not source.is_file() or not source.resolve().is_relative_to(ROOT):
        raise RuntimeError('Invalid package source: ' + relative)
    name = 'runtime/' + relative if relative in RUNTIME else relative
    payload[name] = source.read_bytes()
manifest = {
    'name': 'InfraCore Phase 3 delta - 2026-10-19',
    'scope': 'Manual hosting UAT; existing Phase 2 required. No DB tests executed.',
    'runtime_files': len(RUNTIME),
    'files': [{'path': name, 'bytes': len(data), 'sha256': sha256(data).hexdigest()}
              for name, data in payload.items()],
}
manifest_bytes = json.dumps(manifest, ensure_ascii=False, indent=2).encode('utf-8')
snapshot = sha256(manifest_bytes).hexdigest()[:12]
output = ROOT / 'artifacts' / 'ita-phase03'
output.mkdir(parents=True, exist_ok=True)
archive = output / ('infracore-phase03-' + snapshot + '.zip')
readme = '''InfraCore Phase 3 - manual hosting UAT

Existing Phase 2 must already run on hosting. Back up DB and source first.
Extract locally. Upload CONTENTS of runtime/ to:
/domains/infracore.dung.derasoft.com/public_html
Keep path structure. Do not upload the runtime folder itself as a nested folder.
Do not upload manifest/docs/README or overwrite private DB config/auth_key.
No SQL migration required. Do not re-import phase01/005 SQL.
Upload new PHP services before bootstrap/application; JS/CSS/templates last.
Then Ctrl+F5. Follow docs/PHASE03_HOST_TEST.md.
Pure/scripted/browser mock checks are NOT DB integration tests.
For rollback, restore backed-up runtime files. Preserve new asset/audit data.
'''
with ZipFile(archive, 'w', ZIP_DEFLATED) as bundle:
    for name, data in payload.items():
        bundle.writestr(name, data)
    bundle.writestr('manifest.json', manifest_bytes)
    bundle.writestr('README.txt', readme.encode('utf-8'))
with ZipFile(archive) as bundle:
    for item in manifest['files']:
        if sha256(bundle.read(item['path'])).hexdigest() != item['sha256']:
            raise RuntimeError('Package hash mismatch')
print(json.dumps({'archive': str(archive), 'snapshot': snapshot,
                  'runtime_files': len(RUNTIME), 'sha256': sha256(archive.read_bytes()).hexdigest()}))
