"""Build an offline F5.4 delta from committed runtime + locked dependencies. Never deploys."""
from pathlib import Path
import hashlib
import json
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[1]
RUNTIME = [
    "classes/services/itahandoverpdf.class.php", "classes/services/itahandoverservice.class.php",
    "classes/services/itaapplication.class.php", "classes/services/itaauditservice.class.php",
    "classes/dao/itaassignmentobjects.class.php", "includes/ita_bootstrap.inc.php",
    "assets/ita/js/ita-assignments-live.js", "templates/admin/ita/layout.tpl.html",
    "templates/admin/ita/partials/assignments.tpl.html", "templates/admin/ita/handoverpdf.tpl.html",
    "classes/ita-pdf/composer.json", "classes/ita-pdf/composer.lock", "classes/ita-pdf/README.md",
]


def git(*args):
    return subprocess.check_output(["git", "-c", f"safe.directory={ROOT.as_posix()}", *args], cwd=ROOT)


def main():
    for name in RUNTIME:
        git("ls-files", "--error-unmatch", "--", name)
    if git("diff", "HEAD", "--", *RUNTIME):
        raise SystemExit("Commit runtime source before packaging.")
    commit = git("rev-parse", "HEAD").decode().strip()
    vendor = ROOT / "classes/ita-pdf/vendor"
    locked = json.loads((ROOT / "classes/ita-pdf/composer.lock").read_text(encoding="utf-8"))
    installed = json.loads((vendor / "composer/installed.json").read_text(encoding="utf-8"))
    versions = lambda packages: {p["name"]: p["version"].lstrip("v") for p in packages}
    if versions(locked["packages"]) != versions(installed["packages"]):
        raise SystemExit("Installed packages do not match lock file.")
    paths = [ROOT / name for name in RUNTIME] + sorted(p for p in vendor.rglob("*") if p.is_file())
    inventory = []
    for path in paths:
        if path.is_symlink() or not path.resolve().is_relative_to(ROOT):
            raise SystemExit("Refusing linked/outside-workspace file.")
        data = path.read_bytes()
        inventory.append({"path": path.relative_to(ROOT).as_posix(), "bytes": len(data), "sha256": hashlib.sha256(data).hexdigest()})
    output = ROOT / "artifacts/ita-phase05"
    output.mkdir(parents=True, exist_ok=True)
    archive = output / f"ita-pdf-{commit[:12]}.zip"
    manifest = {"source_commit": commit, "base_checkpoint": "bfe3539", "kind": "PDF delta, not full app or DB backup", "files": inventory}
    with zipfile.ZipFile(archive, "w", zipfile.ZIP_DEFLATED) as bundle:
        for path, entry in zip(paths, inventory):
            bundle.write(path, entry["path"])
        bundle.writestr("MANIFEST.json", json.dumps(manifest, ensure_ascii=False, indent=2))
    with zipfile.ZipFile(archive) as bundle:
        for entry in inventory:
            if hashlib.sha256(bundle.read(entry["path"])).hexdigest() != entry["sha256"]:
                raise SystemExit("Archive read-back hash mismatch.")
    print(json.dumps({"archive": str(archive), "source_commit": commit, "files": len(paths), "sha256": hashlib.sha256(archive.read_bytes()).hexdigest()}))


if __name__ == "__main__":
    main()
