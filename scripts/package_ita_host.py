"""Cumulative ITA update for an existing Phase 2 host. Never deploys or reads DB config."""
from pathlib import Path
import hashlib
import json
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]


def git(*args):
    return subprocess.check_output(['git', '-c', f'safe.directory={ROOT.as_posix()}', *args], cwd=ROOT)


def safe(path):
    if not path.is_file() or not path.resolve().is_relative_to(ROOT):
        raise RuntimeError('Invalid package path: ' + str(path))
    for part in [path, *path.parents]:
        if part == ROOT:
            break
        if part.is_symlink() or part.is_junction():
            raise RuntimeError('Linked package path: ' + str(path))
    return path.read_bytes()


def main():
    tracked = git('ls-files').decode().splitlines()
    prefixes = ('assets/ita/css/', 'assets/ita/js/', 'assets/ita/vendor/',
                'classes/dao/ita', 'classes/security/ita', 'classes/services/ita',
                'classes/database/ita', 'classes/ita-pdf/', 'templates/admin/ita/')
    exact = {'includes/ita_bootstrap.inc.php', 'includes/ita_mode.inc.php',
             'includes/project_gate.inc.php', 'modules/admin/ita.module.php'}
    names = [n for n in tracked if n.startswith(prefixes) or n in exact]
    if git('diff', 'HEAD', '--', *names):
        raise RuntimeError('Commit runtime changes before packaging')
    commit = git('rev-parse', 'HEAD').decode().strip()
    payload = {}
    origins = {}
    for name in names:
        safe(ROOT / name)
        payload['runtime/' + name] = git('show', commit + ':' + name)
        origins['runtime/' + name] = 'git:' + commit
    vendor = ROOT / 'classes/ita-pdf/vendor'
    lock = json.loads(payload['runtime/classes/ita-pdf/composer.lock'])
    installed = json.loads(safe(vendor / 'composer/installed.json'))
    versions = lambda ps: {p['name']: p['version'].lstrip('v') for p in ps}
    if versions(lock['packages']) != versions(installed['packages']):
        raise RuntimeError('PDF vendor does not match lock')
    roots = [vendor] + [ROOT / 'classes/PhpSpreadSheet' / n for n in ['PhpOffice', 'MyCLabs', 'Psr', 'ZipStream']]
    for directory in roots:
        if not directory.is_dir():
            raise RuntimeError('Missing runtime dependency: ' + str(directory))
        for path in sorted(directory.rglob('*')):
            if path.is_symlink() or path.is_junction():
                raise RuntimeError('Linked dependency')
            if path.is_file():
                if path.relative_to(ROOT).as_posix() == 'classes/PhpSpreadSheet/PhpOffice/PhpSpreadsheet/Calculation/locale/Translations.xlsx':
                    continue  # Maintainer translation workbook, not a runtime resource.
                if path.suffix.lower() in {'.sql', '.log', '.bak', '.xlsx', '.csv', '.env'}:
                    raise RuntimeError('Unexpected dependency data: ' + str(path))
                name = 'runtime/' + path.relative_to(ROOT).as_posix()
                payload[name] = safe(path)
                origins[name] = 'locked PDF vendor' if path.is_relative_to(vendor) else 'existing bundled Excel dependency; content hash below'
    font = 'assets/fonts/inter/InterVariable.woff2'
    payload['runtime/' + font] = safe(ROOT / font)
    origins['runtime/' + font] = 'existing bundled font'
    for name in ['docs/HOST_UPLOAD_PHASE07.md', 'docs/PHASE07_HOST_TEST.md', 'docs/PHASE06_HOST_TEST.md']:
        payload['handoff/' + Path(name).name] = safe(ROOT / name)
    for name in ['scripts/check_ita_license.php', 'classes/security/itahttperror.class.php', 'classes/services/italicensecrypto.class.php']:
        payload['handoff/preflight/' + name] = safe(ROOT / name)
    inventory = [{'path': n, 'bytes': len(data), 'sha256': hashlib.sha256(data).hexdigest(),
                  'origin': origins.get(n, 'handoff document/tool; do not upload to webroot')} for n, data in sorted(payload.items())]
    manifest = {'source_commit': commit, 'kind': 'cumulative update, existing Phase 2 application required',
                'target': '/domains/infracore.dung.derasoft.com/public_html',
                'excluded': ['private config', 'secrets', 'SQL', 'DB dumps', 'sessions', 'logs', 'mock data', 'tests'],
                'requires_existing': ['admin.php and legacy runtime', 'Smarty 4.5.5', 'PmAuth/PmDb', 'existing ITA database and auth config', '.ita-enabled'],
                'files': inventory}
    payload['MANIFEST.json'] = json.dumps(manifest, ensure_ascii=False, indent=2).encode('utf-8')
    folder = ROOT / 'artifacts/ita-phase07'
    folder.mkdir(parents=True, exist_ok=True)
    archive = folder / f'infracore-host-through-phase07-{commit[:12]}.zip'
    with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as bundle:
        for name, data in sorted(payload.items()):
            bundle.writestr(name, data)
    with zipfile.ZipFile(archive) as bundle:
        if bundle.testzip() or set(bundle.namelist()) != set(payload):
            raise RuntimeError('Archive inventory/CRC mismatch')
        for entry in inventory:
            if hashlib.sha256(bundle.read(entry['path'])).hexdigest() != entry['sha256']:
                raise RuntimeError('Read-back hash mismatch')
    result = {'archive': str(archive), 'source_commit': commit, 'files': len(inventory),
              'runtime_files': len(origins), 'bytes': archive.stat().st_size,
              'sha256': hashlib.sha256(archive.read_bytes()).hexdigest()}
    (folder / 'verification.json').write_text(json.dumps(result, indent=2), encoding='utf-8')
    print(json.dumps(result))


if __name__ == '__main__':
    main()
