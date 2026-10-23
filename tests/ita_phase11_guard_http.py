"""Real local PHP HTTP in an isolated source fixture. No DB, host, real markers or deploy."""
import http.client
import pathlib
import shutil
import socket
import subprocess
import tempfile
import time

ROOT = pathlib.Path(__file__).resolve().parents[1]
checks = 0


def check(value, label):
    global checks
    if not value:
        raise AssertionError(label)
    checks += 1


with tempfile.TemporaryDirectory(prefix='ita-guard-', dir=ROOT / 'tmp') as folder:
    base = pathlib.Path(folder)
    (base / 'includes').mkdir()
    (base / 'modules/admin').mkdir(parents=True)
    for name in ['project-start.php', 'router.php', 'includes/ita_mode.inc.php', 'includes/project_gate.inc.php']:
        shutil.copyfile(ROOT / name, base / name)
    for name in ['admin.php', 'index.php']:
        (base / name).write_text("<?php require_once __DIR__.'/includes/project_gate.inc.php'; echo 'LEGACY_FIXTURE';", encoding='utf-8')
    (base / 'modules/admin/ita.module.php').write_text("<?php if (!defined('ITA_REQUEST_ALLOWED')) { http_response_code(404);exit; } echo 'ITA_FIXTURE_NO_DB';exit;", encoding='utf-8')
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', 0))
        port = probe.getsockname()[1]
    log = open(base / 'server.log', 'wb')
    # No router: direct PHP requests exercise the entry gate without Apache rewrites.
    process = subprocess.Popen([shutil.which('php'), '-S', f'127.0.0.1:{port}', '-t', str(base)], stdout=log, stderr=log)
    try:
        for _ in range(100):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.1):
                    break
            except OSError:
                if process.poll() is not None:
                    raise RuntimeError('PHP fixture failed to start')
                time.sleep(.05)
        else:
            raise RuntimeError('PHP fixture not ready')

        def request(path, method='GET'):
            connection = http.client.HTTPConnection('127.0.0.1', port, timeout=4)
            try:
                connection.request(method, path, body=b'' if method == 'POST' else None)
                response = connection.getresponse()
                return response.status, dict(response.getheaders()), response.read()
            finally:
                connection.close()

        (base / '.ita-enabled').touch()
        for method in ['GET', 'HEAD', 'POST']:
            for suffix in ['', '?worker=1', '?op=ita', '?ITA_LANDING_ALLOWED=1']:
                status, headers, body = request('/project-start.php' + suffix, method)
                check(status == (403 if method == 'POST' else 303), f'direct {method}/{suffix}')
                check(b'LEGACY_FIXTURE' not in body and b'self.addEventListener' not in body, 'No landing/worker bypass')
                if method == 'HEAD':
                    check(body == b'', 'HEAD no body')
        status, headers, body = request('/admin.php?op=ita')
        check(status == 200 and body == b'ITA_FIXTURE_NO_DB', 'ITA dispatch once, no legacy/DB')

        # Internal legacy-hidden landing must still work once, without recursive includes.
        (base / '.ita-enabled').unlink()
        (base / '.legacy-hidden').touch()
        status, headers, body = request('/project-start.php')
        check(status == 200 and body.count(b'<main>') == 1, 'Legacy-hidden landing once')
        status, headers, body = request('/index.php')
        check(status == 200 and body.count(b'<main>') == 1 and b'LEGACY_FIXTURE' not in body, 'Internal landing once')
        # Worker URI normally rewritten by Apache; directly invoke PHP fixture using PATH_INFO.
        status, headers, body = request('/index.php/service-worker.js')
        check(status == 200 and body.count(b"self.addEventListener('install'") == 1, 'Legacy-hidden worker once')

        (base / '.ita-enabled').touch()
        (base / '.ita-legacy-enabled').touch()
        status, headers, body = request('/index.php')
        check(status == 200 and body == b'LEGACY_FIXTURE', 'Explicit legacy restoration preserved')
        (base / '.ita-legacy-enabled').unlink()
        # Internal worker test invokes the real gate with the original URI, no DB bootstrap.
        (base / 'worker-probe.php').write_text("<?php $_SERVER['REQUEST_URI']='/service-worker.js'; require __DIR__.'/includes/project_gate.inc.php';", encoding='utf-8')
        for method in ['GET', 'HEAD', 'POST']:
            status, headers, body = request('/worker-probe.php', method)
            check(status == (403 if method == 'POST' else 200), 'ITA worker method ' + method)
            if method == 'GET':
                check(body.count(b"self.addEventListener('install'") == 1 and headers.get('Content-Type', '').startswith('application/javascript'), 'ITA worker once and MIME')
            if method == 'HEAD':
                check(body == b'', 'ITA worker HEAD empty')
    finally:
        process.terminate()
        process.wait(timeout=5)
        log.close()
print(f'{checks} real PHP local HTTP fixture checks passed; no DB, no Apache/host, only copied fixture markers.')
