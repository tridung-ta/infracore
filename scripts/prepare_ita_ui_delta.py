"""Prepare a fixed 19-file FE delta review; no ZIP, DB, host or private config access."""
import argparse
import json
from prepare_ita_release import ROOT, git
from ita_release_validation import digest, validate_inventory

RUNTIME = (
    'assets/ita/css/ita-tokens.css',
    'assets/ita/css/ita-refinements.css',
    'assets/ita/css/ita-focus.css',
    'assets/ita/css/ita-login.css',
    'assets/ita/css/ita-network.css',
    'assets/ita/js/ita-appearance.js',
    'assets/ita/js/ita-assignments-live.js',
    'assets/ita/js/ita-assignments.js',
    'assets/ita/js/ita-dashboard.js',
    'assets/ita/js/ita-form-guidance.js',
    'assets/ita/js/ita-network-3d.js',
    'assets/ita/js/ita-network-scene.js',
    'assets/ita/js/ita-network.js',
    'assets/ita/js/motion/ita-core.js',
    'templates/admin/ita/layout.tpl.html',
    'templates/admin/ita/login.tpl.html',
    'templates/admin/ita/partials/icons.tpl.html',
    'templates/admin/ita/partials/sidebar.tpl.html',
    'templates/admin/ita/partials/network.tpl.html',
)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', required=True)
    parser.add_argument('--output', required=True)
    args = parser.parse_args()
    commit = git('rev-parse', '--verify', args.source + '^{commit}').decode().strip()
    target = ROOT / args.output
    if not target.resolve().is_relative_to(ROOT / 'artifacts') or target.exists():
        raise ValueError('Use a new review path inside artifacts')
    for path in [target, *target.parents]:
        if path.is_symlink() or path.is_junction():
            raise ValueError('Linked output refused')
        if path == ROOT:
            break
    files = []
    for path in sorted(RUNTIME):
        data = git('show', commit + ':' + path)
        files.append({'path': 'runtime/' + path, 'size_bytes': len(data),
                      'sha256': digest(data), 'origin': 'git', 'version_or_ref': commit})
    validate_inventory(files, {})
    if len(files) != 19 or any(item['path'].endswith('.php') for item in files):
        raise ValueError('Unexpected FE delta scope')
    plan = {'kind': 'REVIEW INVENTORY ONLY - NOT A RELEASE/ZIP',
            'package_type': 'UI FE DELTA - EXACTLY 19 RUNTIME FILES - NO PHP SQL CONFIG',
            'source_commit': commit,
            'required_baseline': 'Existing compatible InfraCore cumulative runtime and Three.js 0.180.0; not standalone',
            'uploaded': False, 'database_tested': False, 'php83_tested': False,
            'special_path_hashes_for_review': {}, 'files': files}
    target.parent.mkdir(parents=True, exist_ok=True)
    with target.open('x', encoding='utf-8', newline='\n') as handle:
        handle.write(json.dumps(plan, ensure_ascii=False, indent=2) + '\n')
    print(json.dumps({'source_commit': commit, 'files': len(files),
                      'bytes': sum(item['size_bytes'] for item in files), 'review': args.output}))


if __name__ == '__main__':
    main()
