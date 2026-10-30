"""Build an explicitly reviewed cumulative test ZIP. Never executes PHP/DB or uploads."""
import argparse
import json
import platform
import re
import zipfile
from datetime import datetime, timezone
from pathlib import Path
from prepare_ita_release import ROOT, EXACT, PREFIXES, HANDOFF, SQL, git, safe_read
from ita_release_validation import checksums, digest, validate_inventory, validate_zip


def build(review, output, php_version):
    review_path = ROOT / review
    target = ROOT / output
    if not review_path.resolve().is_relative_to(ROOT / 'artifacts'):
        raise ValueError('Review must be inside artifacts')
    # safe_read rejects links/junctions in every existing ancestor.
    plan = json.loads(safe_read(review_path))
    if plan.get('kind') != 'REVIEW INVENTORY ONLY - NOT A RELEASE/ZIP':
        raise ValueError('Expected separate review inventory')
    if not target.resolve().is_relative_to(ROOT / 'artifacts') or target.suffix != '.zip' or target.exists():
        raise ValueError('Use a new artifacts ZIP path')
    for parent in target.parents:
        if parent.is_symlink() or parent.is_junction():
            raise ValueError('Linked output refused')
        if parent == ROOT:
            break
    commit = git('rev-parse', '--verify', plan['source_commit'] + '^{commit}').decode().strip()
    if commit != plan['source_commit']:
        raise ValueError('Full fixed commit required')
    exceptions = plan['special_path_hashes_for_review']
    validate_inventory(plan['files'], exceptions)
    source_map = {'manual-review/.htaccess': '.htaccess'}
    source_map.update({'handoff/' + Path(p).name: p for p in HANDOFF})
    source_map.update({'handoff/sql/' + Path(p).name: p for p in SQL})
    payload = []
    for item in plan['files']:
        name = item['path']
        if name.startswith('runtime/'):
            relative = name[len('runtime/'):]
            if item['origin'] == 'git':
                if not (relative in EXACT or relative.startswith(PREFIXES) or relative in {'classes/ita-pdf/composer.json','classes/ita-pdf/composer.lock'}):
                    raise ValueError('Git path outside runtime allowlist')
                data = git('show', commit + ':' + relative)
            else:
                if not relative.startswith(('classes/ita-pdf/vendor/','classes/PhpSpreadSheet/','assets/fonts/inter/')):
                    raise ValueError('Unknown dependency origin/path')
                data = safe_read(ROOT / relative)
        elif name in source_map:
            data = git('show', commit + ':' + source_map[name])
        else:
            raise ValueError('Path not in reviewed payload sources')
        if len(data) != item['size_bytes'] or digest(data) != item['sha256']:
            raise ValueError('Payload changed after review: ' + name)
        payload.append((name, data))
    if not re.fullmatch(r'\d+\.\d+\.\d+', php_version):
        raise ValueError('Record actual separately verified CLI PHP version')
    manifest = dict(plan, kind='CUMULATIVE HOST TEST UPDATE - NOT DB/HOST ACCEPTANCE',
                    release_id=target.stem, built_at_utc=datetime.now(timezone.utc).isoformat(),
                    build_environment={'os': platform.system(), 'python': platform.python_version(),
                                       'php_cli_verified_separately': php_version, 'php83_tested': False})
    manifest_bytes = (json.dumps(manifest, ensure_ascii=False, indent=2) + '\n').encode('utf-8')
    sums = checksums(manifest['files'])
    target.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(target, 'x', compression=zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
        for name, data in payload:
            archive.writestr(name, data)
        archive.writestr('MANIFEST.json', manifest_bytes)
        archive.writestr('SHA256SUMS.txt', sums)
    # A failed ZIP remains an unverified artifact; no success report/sidecar is issued.
    result = validate_zip(target, manifest, exceptions)
    sidecar = target.with_suffix('.checksums.txt')
    report = target.with_suffix('.verify.json')
    if sidecar.exists() or report.exists():
        raise ValueError('Sidecars already exist; no overwrite')
    zip_hash = digest(safe_read(target))
    sidecar.write_text(digest(manifest_bytes) + '  MANIFEST.json\n' + digest(sums) + '  SHA256SUMS.txt\n' + zip_hash + '  ' + target.name + '\n', encoding='utf-8', newline='\n')
    result.update(source_commit=commit, zip=output, zip_size_bytes=target.stat().st_size,
                  zip_sha256=zip_hash, review_sha256=digest(safe_read(review_path)),
                  validation='actual ZIP reread: allowlist/path/CRC/size/SHA256/metadata passed',
                  uploaded=False, php83_tested=False, database_tested=False)
    report.write_text(json.dumps(result, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
    print(json.dumps(result))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--review', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--php-version', required=True, help='Actual CLI version verified separately, not target host version')
    args = parser.parse_args()
    build(args.review, args.output, args.php_version)
