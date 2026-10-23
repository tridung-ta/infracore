<?php
/** Protected ITA module; legacy credentials are reused with a separate IT session. */
if (!defined('ITA_REQUEST_ALLOWED') || ITA_REQUEST_ALLOWED !== true) {
    http_response_code(404);
    exit;
}

ini_set('display_errors', '0');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");

require_once dirname(__DIR__,2).'/classes/security/itaerrorresponse.class.php';
try {
    require_once dirname(__DIR__, 2) . '/includes/ita_bootstrap.inc.php';
    (new ItaApplication())->run();
} catch (Throwable) {
    $action=$_GET['act'] ?? '';
    ItaErrorResponse::send(500,'Hệ thống gặp lỗi. Vui lòng thử lại sau.',
        !is_string($action) || ($action!=='' && !in_array($action,['login','logout'],true)),ItaErrorResponse::report(500,'bootstrap'));
}
exit;
