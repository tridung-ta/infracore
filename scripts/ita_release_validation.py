"""Read-only release inventory/ZIP validation. Never extracts or deploys."""
import hashlib
import json
import re
import stat
import zipfile


def digest(data):
    return hashlib.sha256(data).hexdigest()


def validate_path(path, sha256, exceptions=None):
    exceptions = exceptions or {}
    if not isinstance(path, str) or not path or '\\' in path or ':' in path or '\x00' in path or path.startswith('/'):
        raise ValueError('Unsafe release path')
    parts = path.split('/')
    if any(not p or p in {'.', '..'} or p.rstrip(' .') != p or any(ord(c) < 32 for c in p) for p in parts):
        raise ValueError('Ambiguous release path')
    lowered = [p.casefold() for p in parts]
    base = lowered[-1]
    if set(lowered) & {'tmp', 'tests', '.git', '.playwright-cli', 'artifacts', 'scripts', 'node_modules', 'templates_c'}:
        raise ValueError('Excluded development path: ' + path)
    if any(lowered[i:i+3] in [['assets', 'ita', 'mock'], ['assets', 'ita', 'preview']] for i in range(len(lowered))):
        raise ValueError('Excluded MOCK/preview path: ' + path)
    if base in {'dashboard.html', 'reports.html', 'login-preview.html', '.ita-legacy-enabled'} or '-preview.' in base:
        raise ValueError('Excluded preview/legacy marker: ' + path)
    if base.startswith('.env') or base == 'ita_database.local.php' or base.endswith(('.pem', '.key', '.log', '.bak', '.orig', '.swp')):
        raise ValueError('Private/data path refused: ' + path)
    sensitive = base in {'.htaccess', '.user.ini'} or base.startswith('.ita-') or base.endswith(('.sql', '.ini'))
    if sensitive and exceptions.get(path) != sha256:
        raise ValueError('Sensitive path needs exact reviewed hash: ' + path)
    if base.endswith('.php') and set(lowered[:-1]) & {'upload', 'uploads', 'staging'}:
        raise ValueError('Executable in upload/staging: ' + path)


def validate_inventory(files, exceptions=None):
    seen = set()
    for item in files:
        path = item['path']
        if not re.fullmatch('[a-f0-9]{64}', item['sha256']) or type(item['size_bytes']) is not int or item['size_bytes'] < 0:
            raise ValueError('Invalid file metadata')
        validate_path(path, item['sha256'], exceptions)
        if path.casefold() in seen:
            raise ValueError('Duplicate case-insensitive release path')
        seen.add(path.casefold())
    if not files:
        raise ValueError('Empty release inventory')


def checksums(files):
    return ''.join(item['sha256'] + '  ' + item['path'] + '\n' for item in sorted(files, key=lambda r: r['path'])).encode('utf-8')


def validate_zip(source, reviewed_manifest, exceptions=None):
    """Caller supplies a separately reviewed allowlist, not an archive's self-claim."""
    reviewed_files = reviewed_manifest['files']
    if not re.fullmatch('[a-f0-9]{40}', reviewed_manifest.get('source_commit', '')):
        raise ValueError('Invalid reviewed source commit')
    validate_inventory(reviewed_files, exceptions)
    expected = {item['path']: item for item in reviewed_files}
    metadata = {'MANIFEST.json', 'SHA256SUMS.txt'}
    if set(expected) & metadata:
        raise ValueError('Metadata cannot self-hash in payload')
    with zipfile.ZipFile(source) as archive:
        entries = archive.infolist()
        if len(entries) > 6000 or sum(i.file_size for i in entries) > 250 * 1024 * 1024:
            raise ValueError('Archive exceeds release limits')
        seen = set()
        for info in entries:
            name = info.filename
            if info.orig_filename != name or info.external_attr & 0x400:
                raise ValueError('Truncated/reparse-point ZIP name refused')
            if info.is_dir() or stat.S_IFMT(info.external_attr >> 16) not in {0, stat.S_IFREG}:
                raise ValueError('Directory/link/special ZIP entries refused')
            if info.flag_bits & 1 or info.file_size > 32 * 1024 * 1024:
                raise ValueError('Encrypted/oversized entry refused')
            if name.casefold() in seen:
                raise ValueError('Duplicate ZIP path')
            seen.add(name.casefold())
            validate_path(name, expected.get(name, {}).get('sha256', ''), exceptions)
        if {i.filename for i in entries} != set(expected) | metadata:
            raise ValueError('ZIP differs from reviewed inventory')
        manifest = json.loads(archive.read('MANIFEST.json'))
        if manifest != reviewed_manifest:
            raise ValueError('Manifest differs from reviewed inventory')
        if archive.read('SHA256SUMS.txt') != checksums(reviewed_files):
            raise ValueError('SHA256SUMS mismatch')
        for path, item in expected.items():
            data = archive.read(path)  # CRC checked by zipfile while reading.
            if len(data) != item['size_bytes'] or digest(data) != item['sha256']:
                raise ValueError('Entry size/hash mismatch: ' + path)
        return {'files': len(expected), 'uncompressed_bytes': sum(i['size_bytes'] for i in reviewed_files)}
