"""Prepare a review inventory only. Does NOT build ZIP, execute PHP or access DB/host."""
from pathlib import Path
import argparse
import json
import subprocess
from ita_release_validation import digest, validate_inventory

ROOT = Path(__file__).resolve().parents[1]
EXACT = {'project-start.php', 'includes/ita_bootstrap.inc.php', 'includes/ita_mode.inc.php',
         'includes/project_gate.inc.php', 'modules/admin/ita.module.php',
         'classes/database/pmdb.class.php', 'classes/security/pmauth.class.php'}
PREFIXES = ('assets/ita/css/', 'assets/ita/js/', 'assets/ita/vendor/',
            'classes/dao/ita', 'classes/security/ita', 'classes/services/ita',
            'classes/database/ita', 'templates/admin/ita/')
HANDOFF = ['docs/PREUPLOAD_SECURITY_VERIFY.md', 'docs/PHASE13_SQL_HOST_GUIDE.md', 'docs/PHASE13_BE_VERIFY.md',
           'docs/PHASE11_HOST_TEST.md', 'docs/PHASE09_HOST_TEST.md',
           'docs/PHASE10_HOST_TEST.md', 'docs/HOST_SOURCE_IDENTIFICATION_RESULT.md',
           'docs/HOST_UPLOAD_20261026.md', 'docs/S26_AUTH_VERIFY.md',
           'docs/BE_GATE_HOST_MIGRATIONS_005_006.md']
SQL = ['database/migrations/006_ita_network_links.sql', 'database/host/phase13_preflight.sql',
       'database/host/phase13_verify.sql', 'docs/PHASE08_EXPLAIN.sql',
       'docs/PHASE09_EXPLAIN.sql', 'docs/PHASE10_EXPLAIN.sql', 'docs/PHASE13_EXPLAIN.sql']


def git(*args):
    return subprocess.run(['git', '-c', 'safe.directory=' + ROOT.as_posix(), *args], cwd=ROOT, check=True, capture_output=True).stdout


def safe_read(path):
    for candidate in [path, *path.parents]:
        if candidate.is_symlink() or candidate.is_junction():
            raise ValueError('Linked dependency refused')
        if candidate == ROOT:
            break
    if not path.resolve().is_relative_to(ROOT) or not path.is_file():
        raise ValueError('Dependency outside workspace/missing')
    return path.read_bytes()


def dependency_files(folder):
    stack, found = [folder], []
    while stack:
        path = stack.pop()
        if path.is_symlink() or path.is_junction():
            raise ValueError('Linked dependency refused')
        if path.is_dir():
            stack.extend(path.iterdir())
        elif path.is_file():
            found.append(path)
    return sorted(found, key=lambda p: p.relative_to(folder).as_posix())


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', required=True)
    parser.add_argument('--output', required=True, help='New JSON review file inside artifacts/')
    args = parser.parse_args()
    commit = git('rev-parse', '--verify', args.source + '^{commit}').decode().strip()
    target = ROOT / args.output
    if not target.resolve().is_relative_to(ROOT / 'artifacts') or target.exists():
        raise ValueError('Use a new artifacts review path')
    files, exceptions = [], {}
    def add(path, data, origin, version, exceptional=False):
        files.append({'path': path, 'size_bytes': len(data), 'sha256': digest(data), 'origin': origin, 'version_or_ref': version})
        if exceptional:
            exceptions[path] = digest(data)
    tracked = git('ls-tree', '-r', '--name-only', commit).decode().splitlines()
    for name in tracked:
        if (name.startswith(PREFIXES) or name in EXACT) and '-preview.' not in name:
            add('runtime/' + name, git('show', commit + ':' + name), 'git', commit)
    for name in ['classes/ita-pdf/composer.json', 'classes/ita-pdf/composer.lock']:
        add('runtime/' + name, git('show', commit + ':' + name), 'git', commit)
    add('manual-review/.htaccess', git('show', commit + ':.htaccess'), 'git: existing approved rules; manual host review required', commit, True)
    for name in HANDOFF:
        add('handoff/' + Path(name).name, git('show', commit + ':' + name), 'git: not webroot', commit)
    for name in SQL:
        add('handoff/sql/' + Path(name).name, git('show', commit + ':' + name), 'git: user executes separately; not webroot', commit, True)
    lock = json.loads(git('show', commit + ':classes/ita-pdf/composer.lock'))
    installed = json.loads(safe_read(ROOT / 'classes/ita-pdf/vendor/composer/installed.json'))
    def packages(rows):
        return {p['name']: (p['version'].lstrip('v'), p.get('source', {}).get('reference'), p.get('dist', {}).get('reference')) for p in rows}
    if packages(lock['packages']) != packages(installed['packages']):
        raise ValueError('PDF installed version/refs differ from committed lock')
    dependency_counts = {}
    for folder, origin, ref in [('classes/ita-pdf/vendor', 'PDF installed tree; lock refs checked', digest(git('show', commit + ':classes/ita-pdf/composer.lock'))),
                                ('classes/PhpSpreadSheet', 'legacy Excel baseline; upstream version unknown', 'baseline-tree'),
                                ('assets/fonts/inter', 'Inter baseline; upstream ref unverified', 'baseline-files')]:
        paths = dependency_files(ROOT / folder)
        if not paths:
            raise ValueError('Missing dependency: ' + folder)
        manifest = ''.join(digest(safe_read(p)) + '  ' + p.relative_to(ROOT / folder).as_posix() + '\n' for p in paths)
        tree_hash = digest(manifest.encode('utf-8'))
        if folder == 'classes/PhpSpreadSheet' and (len(paths) != 566 or tree_hash != '888154326da0fc145d3e6d0d6810818c63881882fa0819ad7f77f93ef2dbe797'):
            raise ValueError('Excel differs from documented baseline')
        count = 0
        for path in paths:
            relative = path.relative_to(ROOT).as_posix()
            if '/tmp/' in relative or relative.endswith('/Calculation/locale/Translations.xlsx'):
                continue
            add('runtime/' + relative, safe_read(path), origin, ref)
            count += 1
        dependency_counts[folder] = {'source_files': len(paths), 'selected_files': count, 'source_tree_sha256': tree_hash}
    for name, expected in [('InterVariable.woff2', '693b77d4f32ee9b8bfc995589b5fad5e99adf2832738661f5402f9978429a8e3'), ('OFL.txt', 'b3195af0fb14368d1b3b10fb9d3fe503b7163ea083859d2ee553bc74da07c320')]:
        if digest(safe_read(ROOT / 'assets/fonts/inter' / name)) != expected:
            raise ValueError('Font differs from documented baseline')
    files.sort(key=lambda f: f['path'])
    validate_inventory(files, exceptions)
    plan = {'kind': 'REVIEW INVENTORY ONLY - NOT A RELEASE/ZIP', 'source_commit': commit,
            'host_baseline_evidence': '4 sample files match Phase8; full host source/dependencies unverified',
            'requires_existing': ['admin.php/ajax.php project_gate entrypoints', 'native Derasoft runtime and Smarty4.5.5', 'private DB/auth/license configuration', 'existing ITA schema plus approved migration006 applied by user'],
            'special_path_hashes_for_review': exceptions, 'dependencies': dependency_counts,
            'files': files, 'payload_files': len(files), 'payload_bytes': sum(f['size_bytes'] for f in files)}
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(plan, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
    print(json.dumps({'review_file': args.output, 'source_commit': commit, 'files': len(files), 'bytes': plan['payload_bytes'], 'sha256': digest(target.read_bytes()), 'zip_created': False}))


if __name__ == '__main__':
    main()
