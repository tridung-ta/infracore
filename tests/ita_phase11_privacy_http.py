"""Privacy headers through real module/error boundary; fake app, no DB/host/config changes."""
import http.client
import pathlib
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


with tempfile.TemporaryDirectory(prefix='ita-privacy-', dir=ROOT / 'tmp') as folder:
    base = pathlib.Path(folder)
    for name in ['modules/admin/ita.module.php', 'classes/security/itaerrorresponse.class.php']:
        target = base / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(ROOT / name, target)
    (base / 'includes').mkdir()
    (base / 'includes/ita_bootstrap.inc.php').write_text("""<?php
if (isset($_GET['fail'])) { throw new RuntimeException('SYNTHETIC_PRIVATE_EXCEPTION'); }
class ItaApplication {
 public function run(): void {
  switch ($_GET['act'] ?? '') {
   case 'data': header('Content-Type: application/json');echo '{"success":true,"data":[],"message":""}';return;
   case 'forecast-export': header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="synthetic.pdf"');echo '%PDF-SYNTHETIC';return;
   case 'asset-export': header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="synthetic.xlsx"');echo 'PK-SYNTHETIC';return;
   default: echo '<!doctype html><html lang="vi"><title>Module fixture</title><p>HTML_FIXTURE</p></html>';
  }
 }
}
""", encoding='utf-8')
    (base / 'module.php').write_text("<?php define('ITA_REQUEST_ALLOWED',true);require __DIR__.'/modules/admin/ita.module.php';", encoding='utf-8')
    (base / 'error.php').write_text("<?php require __DIR__.'/classes/security/itaerrorresponse.class.php';ItaErrorResponse::send((int)($_GET['status']??404),'Synthetic error',isset($_GET['json']));", encoding='utf-8')
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', 0))
        port = probe.getsockname()[1]
    log = open(base / 'server.log', 'wb')
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
        cases=[('/module.php',200,'text/html'),('/module.php?act=data',200,'application/json'),
               ('/module.php?act=forecast-export',200,'application/pdf'),('/module.php?act=asset-export',200,'application/vnd.openxmlformats'),
               ('/module.php?fail=1',500,'text/html'),('/module.php?fail=1&act=forecast-export',500,'application/json'),
               ('/error.php?status=403',403,'text/html'),('/error.php?status=404&json=1',404,'application/json')]
        for path,status,mime in cases:
            for method in ['GET','HEAD']:
                connection=http.client.HTTPConnection('127.0.0.1',port,timeout=4)
                try:
                    connection.request(method,path)
                    response=connection.getresponse();headers=response.getheaders();body=response.read()
                    def values(name):
                        return [v for k,v in headers if k.lower()==name.lower()]
                    check(response.status==status and values('Content-Type')[0].startswith(mime),'Status/MIME preserved '+path)
                    check(values('Referrer-Policy')==['strict-origin-when-cross-origin'],'One correct referrer header')
                    check(values('Permissions-Policy')==['camera=(), microphone=(), geolocation=()'],'One correct permissions header')
                    check(values('Strict-Transport-Security')==[],'No unapproved HSTS')
                    check(b'SYNTHETIC_PRIVATE_EXCEPTION' not in body and (method!='HEAD' or body==b''),'No exception leak/HEAD body')
                    if path.startswith('/module.php'):
                        check("frame-ancestors 'none'" in values('Content-Security-Policy')[0] and "style-src 'self' 'unsafe-inline'" in values('Content-Security-Policy')[0],'Existing CSP preserved')
                    if status!=200:
                        check(values('Content-Disposition')==[],'Error never becomes attachment')
                    elif method=='GET' and 'export' in path:
                        check(len(values('Content-Disposition'))==1 and body.startswith(b'%PDF' if 'forecast' in path else b'PK'),'Download transport unchanged')
                finally:
                    connection.close()
    finally:
        process.terminate();process.wait(timeout=5);log.close()
print(f'{checks} local PHP HTTP privacy-header checks passed; fake app/download bytes, no DB/host/Apache.')
