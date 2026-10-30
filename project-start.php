<?php
// Only an explicitly authorized internal landing/worker call may skip the entry gate.
if (!defined('ITA_LANDING_ALLOWED') || ITA_LANDING_ALLOWED !== true) {
    require_once __DIR__ . '/includes/project_gate.inc.php';
}
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
if (isset($_GET['worker'])) {
    header('Content-Type: application/javascript; charset=utf-8');
    echo "self.addEventListener('install',()=>self.skipWaiting());self.addEventListener('activate',e=>e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k.startsWith('dera-editorial-static-')).map(k=>caches.delete(k)))).then(()=>self.registration.unregister())));";
    exit;
}
header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'; form-action 'none'");
?>
<!doctype html>
<html lang="vi">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dự án mới</title>
<style>
:root{color-scheme:light}*{box-sizing:border-box}body{margin:0;background:#f8fafc;color:#172033;font:18px/1.6 system-ui,sans-serif}main{max-width:720px;margin:18vh auto;padding:32px}h1{font-size:clamp(30px,6vw,48px);line-height:1.2;margin:0 0 24px}p{max-width:58ch;margin:0;color:#475569}::selection{background:#dbeafe;color:#172033}
</style>
<main><h1>Không gian cho dự án mới</h1><p>Các chức năng và giao diện cũ đã được ẩn. Nội dung dự án mới sẽ được cập nhật tại đây.</p></main>
<script>
if('serviceWorker' in navigator){navigator.serviceWorker.getRegistrations().then(rs=>rs.forEach(r=>r.unregister()));}
if('caches' in window){caches.keys().then(ks=>Promise.all(ks.filter(k=>k.startsWith('dera-editorial-static-')).map(k=>caches.delete(k))));}
</script>
</html>
