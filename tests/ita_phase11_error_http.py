"""Local HTTP error boundary tests: actual PHP/Smarty, isolated transport fixtures, no DB."""
import http.client
import json
import pathlib
import re
import shutil
import socket
import subprocess
import tempfile
import time

ROOT = pathlib.Path(__file__).resolve().parents[1]
checks = 0


def check(ok, label):
    global checks
    if not ok:
        raise AssertionError(label)
    checks += 1


with tempfile.TemporaryDirectory(prefix='ita-errors-', dir=ROOT / 'tmp') as folder:
    base = pathlib.Path(folder)
    for name in ['classes/security/itaerrorresponse.class.php', 'modules/admin/ita.module.php']:
        target = base / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(ROOT / name, target)
    (base / 'includes').mkdir()
    (base / 'includes/ita_bootstrap.inc.php').write_text("<?php throw new RuntimeException('EXCEPTION_SECRET_SENTINEL');", encoding='utf-8')
    root_literal = json.dumps(ROOT.as_posix() + '/')
    (base / 'error.php').write_text("<?php define('ROOT_PATH'," + root_literal + "); require ROOT_PATH.'classes/security/itaerrorresponse.class.php'; require ROOT_PATH.'classes/services/itaapplication.class.php'; $app=new ItaApplication(); (new ReflectionProperty($app,'action'))->setValue($app,$_GET['action']??''); (new ReflectionMethod($app,'error'))->invoke($app,(int)($_GET['status']??404),'<img src=x onerror=alert(1)> Thử lỗi');", encoding='utf-8')
    (base / 'fallback.php').write_text("<?php define('ROOT_PATH',__DIR__.'/missing/'); require __DIR__.'/classes/security/itaerrorresponse.class.php'; ItaErrorResponse::send(500,'EXCEPTION_SECRET_SENTINEL');", encoding='utf-8')
    (base / 'module.php').write_text("<?php define('ROOT_PATH'," + root_literal + "); define('ITA_REQUEST_ALLOWED',true); require __DIR__.'/modules/admin/ita.module.php';", encoding='utf-8')
    (base / 'started.php').write_text("<?php require __DIR__.'/classes/security/itaerrorresponse.class.php'; echo 'ONLY_EXISTING_BYTES'; flush(); ItaErrorResponse::send(500,'DO_NOT_APPEND');", encoding='utf-8')
    (base / 'application.php').write_text("<?php define('ROOT_PATH'," + root_literal + "); foreach (['security/itaerrorresponse','security/itahttperror','security/itainput','services/itaapplication'] as $c) require ROOT_PATH.'classes/'.$c.'.class.php'; class ItaDb { public static function configuration() { throw new RuntimeException('CONFIG_SECRET_SENTINEL'); } } (new ItaApplication())->run();", encoding='utf-8')
    (base / 'sessions').mkdir()
    (base / 'login.php').write_text("<?php define('ROOT_PATH'," + root_literal + "); require ROOT_PATH.'classes/security/itaerrorresponse.class.php'; require ROOT_PATH.'classes/services/itaapplication.class.php'; session_save_path(__DIR__.'/sessions'); session_start(); $_SESSION['ita_csrf']='CSRF_SECRET_SENTINEL'; $app=new ItaApplication(); (new ReflectionProperty($app,'action'))->setValue($app,'login'); (new ReflectionMethod($app,'error'))->invoke($app,403,'<img src=x> Login rejected');", encoding='utf-8')
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', 0))
        port = probe.getsockname()[1]
    log = open(base / 'server.log', 'wb')
    process = subprocess.Popen([shutil.which('php'), '-d', 'log_errors=1', '-d', 'display_errors=1', '-d', 'output_buffering=0', '-S', f'127.0.0.1:{port}', '-t', str(base)], stdout=log, stderr=log)
    try:
        for _ in range(100):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.1):
                    break
            except OSError:
                if process.poll() is not None:
                    raise RuntimeError('PHP error fixture failed to start')
                time.sleep(.05)
        else:
            raise RuntimeError('PHP fixture not ready')

        def request(path, method='GET'):
            connection = http.client.HTTPConnection('127.0.0.1', port, timeout=5)
            try:
                connection.request(method, path, body='REQUEST_BODY_SECRET_SENTINEL' if method == 'POST' else None,
                                   headers={'Cookie': 'test=COOKIE_SECRET_SENTINEL', 'X-Request-ID': 'UNTRUSTED_REFERENCE_SENTINEL'})
                response = connection.getresponse()
                return response.status, dict(response.getheaders()), response.read()
            finally:
                connection.close()

        destination = ROOT / 'tmp/phase11-error-ui'
        destination.mkdir(exist_ok=True)
        for status in [403, 404, 500, 503]:
            code, headers, body = request(f'/error.php?status={status}', 'POST')
            check(code == status and headers['Content-Type'].startswith('text/html'), 'HTML real status/MIME')
            check(b'&lt;img' in body and b'<img src=x' not in body, 'Smarty HTML escaping')
            reference = re.search(rb'<code>([a-f0-9]{16})</code>', body)
            check(reference is not None, 'Server reference shown')
            check('no-store' in headers['Cache-Control'] and headers.get('X-Content-Type-Options') == 'nosniff', 'Uncacheable errors')
            check('Content-Disposition' not in headers and 'Location' not in headers, 'No download/redirect on errors')
            (destination / f'{status}.html').write_bytes(body)
            code, headers, body = request(f'/error.php?status={status}', 'HEAD')
            check(code == status and body == b'', 'HEAD status/no body')
        for action in ['forecast-export', 'asset-export', 'import-errors', 'unknown-action']:
            code, headers, body = request(f'/error.php?action={action}&status=403')
            result = json.loads(body)
            check(code == 403 and headers['Content-Type'].startswith('application/json'), 'API/download errors JSON')
            check(result['success'] is False and result['data'] is None and re.fullmatch('[a-f0-9]{16}', result['request_id']), 'JSON contract/reference')
            check('Content-Disposition' not in headers and not body.startswith((b'PK', b'%PDF')), 'No file bytes on error')
        code, headers, body = request('/error.php?action=asset-export&status=429')
        check(code == 429 and headers.get('Retry-After') == '900', '429 retry hint')
        code, headers, body = request('/fallback.php')
        check(code == 500 and b'M' in body and b'EXCEPTION_SECRET_SENTINEL' not in body, 'Renderer fallback no secret/re-entry')
        for action in ['', 'forecast-export']:
            code, headers, body = request('/module.php?act=' + action)
            check(code == 500 and b'EXCEPTION_SECRET_SENTINEL' not in body, 'Bootstrap exception sanitized')
            check(headers['Content-Type'].startswith('application/json' if action else 'text/html'), 'Bootstrap HTML/API distinction')
        code, headers, body = request('/started.php')
        check(body == b'ONLY_EXISTING_BYTES', 'Already started response not appended/corrupted')
        for path, status, is_json in [('/application.php', 503, False), ('/application.php?act=forecast-export', 503, True),
                                      ('/application.php?act[]=invalid', 422, True)]:
            code, headers, body = request(path)
            check(code == status and headers['Content-Type'].startswith('application/json' if is_json else 'text/html'), 'Application run error contract')
            check(b'CONFIG_SECRET_SENTINEL' not in body and b'Stack trace' not in body, 'Application run sanitized exception')
            if is_json:
                check(json.loads(body)['success'] is False, 'Application JSON envelope')
        code, headers, body = request('/login.php')
        check(code == 403 and headers['Content-Type'].startswith('text/html'), 'Login error preserves form/status')
        check(b'id="login-form"' in body and b'name="csrf_token"' in body and b'&lt;img' in body and b'<img src=x>' not in body, 'Login CSRF form/escaping retained')
    finally:
        process.terminate()
        process.wait(timeout=5)
        log.close()
    text = (base / 'server.log').read_text(encoding='utf-8', errors='replace')
    records = [json.loads(line[line.index('{"event":"ita.error"'):]) for line in text.splitlines() if '{"event":"ita.error"' in line]
    check(bool(records) and all(set(row) == {'event', 'request_id', 'status', 'kind'} for row in records), 'Structured log fields only')
    check(all(secret not in json.dumps(records) for secret in ['REQUEST_BODY_SECRET_SENTINEL', 'COOKIE_SECRET_SENTINEL', 'EXCEPTION_SECRET_SENTINEL', 'UNTRUSTED_REFERENCE_SENTINEL', 'CONFIG_SECRET_SENTINEL', 'CSRF_SECRET_SENTINEL', 'onerror']), 'Application logs no request/exception secrets')
    check(any(row['kind'] == 'render' for row in records) and any(row['kind'] == 'bootstrap' for row in records), 'Fallback/bootstrap logged')
    documents = {str(status): (destination / f'{status}.html').read_text(encoding='utf-8') for status in [403, 404, 500, 503]}
    browser = (ROOT / 'tests/ita_phase11_error_page_browser.js').read_text(encoding='utf-8')
    (destination / 'browser-run.js').write_text(browser.replace('__ERROR_DOCUMENTS__', json.dumps(documents, ensure_ascii=False)), encoding='utf-8')
print(f'{checks} local PHP HTTP/Smarty error checks passed; no DB, no host, no Apache. UI HTML in tmp only.')
