"""Validate offline PDF packaging in a disposable Git fixture, without DB or real credentials."""
import contextlib
import importlib.util
import io
import json
from pathlib import Path
import subprocess
import tempfile
import zipfile

root = Path(__file__).resolve().parents[1]
scratch = root / "tmp/pdfs"
scratch.mkdir(parents=True, exist_ok=True)
spec = importlib.util.spec_from_file_location("pdf_package", root / "scripts/package_ita_pdf.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
with tempfile.TemporaryDirectory(prefix="package-test-", dir=scratch) as temporary:
    fixture = Path(temporary).resolve()
    assert fixture.is_relative_to(scratch.resolve())
    module.ROOT = fixture
    module.RUNTIME = ["classes/services/demo.php"]
    source = fixture / module.RUNTIME[0]
    source.parent.mkdir(parents=True)
    source.write_text("<?php // synthetic runtime\n", encoding="utf-8")
    vendor = fixture / "classes/ita-pdf/vendor/composer"
    vendor.mkdir(parents=True)
    packages = [{"name": "fixture/pdf", "version": "v1.0.0"}]
    (fixture / "classes/ita-pdf/composer.lock").write_text(json.dumps({"packages": packages}), encoding="utf-8")
    installed = vendor / "installed.json"
    installed.write_text(json.dumps({"packages": packages}), encoding="utf-8")
    module.git("init", "-q", "--initial-branch=main")
    module.git("add", "--", module.RUNTIME[0])
    module.git("-c", "user.name=ITA package fixture", "-c", "user.email=fixture@example.invalid", "commit", "-qm", "Synthetic fixture")
    output = io.StringIO()
    with contextlib.redirect_stdout(output):
        module.main()
    archive = Path(json.loads(output.getvalue())["archive"])
    with zipfile.ZipFile(archive) as bundle:
        manifest = json.loads(bundle.read("MANIFEST.json"))
        assert manifest["source_commit"] == module.git("rev-parse", "HEAD").decode().strip()
        assert {row["path"] for row in manifest["files"]} == {module.RUNTIME[0], "classes/ita-pdf/vendor/composer/installed.json"}
    source.write_text("<?php // dirty source\n", encoding="utf-8")
    try:
        module.main()
        raise AssertionError("Dirty source was packaged")
    except SystemExit as error:
        assert "Commit runtime source" in str(error)
    source.write_text("<?php // synthetic runtime\n", encoding="utf-8")
    installed.write_text(json.dumps({"packages": []}), encoding="utf-8")
    try:
        module.main()
        raise AssertionError("Mismatched vendor was packaged")
    except SystemExit as error:
        assert "do not match lock" in str(error)
print("Package fixture passed: ZIP inventory/hash/source revision; dirty source and vendor mismatch blocked.")
