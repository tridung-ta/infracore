<?php
/** Phase 1 presentation module. No database or legacy authentication bootstrap. */
if (!defined('ITA_REQUEST_ALLOWED') || ITA_REQUEST_ALLOWED !== true) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");

try {
    $itaRoot = dirname(__DIR__, 2);
    require_once $itaRoot . '/classes/template/smarty.class.php';
    $itaCompileDir = sys_get_temp_dir() . '/infracore-smarty-' . substr(hash('sha256', $itaRoot), 0, 12);
    if (!is_dir($itaCompileDir) && !mkdir($itaCompileDir, 0770, true) && !is_dir($itaCompileDir)) {
        throw new RuntimeException('ITA template cache unavailable');
    }
    $itaTemplate = new Smarty();
    $itaTemplate->setTemplateDir($itaRoot . '/templates');
    $itaTemplate->setCompileDir($itaCompileDir);
    $itaTemplate->compile_check = true;
    $itaTemplate->debugging = false;
    $itaTemplate->assign('itaRenderer', 'smarty');
    $itaHtml = $itaTemplate->fetch('admin/ita/layout.tpl.html');
    echo $itaHtml;
} catch (Throwable $error) {
    http_response_code(503);
    error_log('ITA presentation unavailable.');
    echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>InfraCore</title><h1>Chưa tải được giao diện</h1><p>Vui lòng thử lại sau hoặc báo quản trị viên.</p></html>';
}
exit;
