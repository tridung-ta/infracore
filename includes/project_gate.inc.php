<?php
/** Reversible isolation before any legacy bootstrap, session or database access. */
require_once __DIR__ . '/ita_mode.inc.php';
if (ITA_ENABLED && !ITA_LEGACY_UI_ENABLED) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "ITA presentation mode does not execute legacy CLI entrypoints.\n");
        exit(3);
    }
    $itaPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $itaDecision = itaRequestDecision($itaPath, $_GET, $_SERVER['REQUEST_METHOD'] ?? 'GET', $itaFlags);
    if ($itaDecision === 'static') {
        return;
    }
    if ($itaDecision === 'worker') {
        $_GET['worker'] = '1';
        require dirname(__DIR__) . '/project-start.php';
        exit;
    }
    if ($itaDecision === 'shell') {
        define('ITA_REQUEST_ALLOWED', true);
        require dirname(__DIR__) . '/modules/admin/ita.module.php';
    }
    if ($itaDecision === 'redirect') {
        header('Cache-Control: no-store, max-age=0');
        header('Location: /admin.php?op=ita&notice=legacy_hidden', true, 303);
        exit;
    }
    http_response_code(['denied' => 403, 'bad_request' => 400][$itaDecision] ?? 404);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    echo 'Yêu cầu không khả dụng trong chế độ giao diện ITA.';
    exit;
}
if (ITA_ENABLED && ITA_LEGACY_UI_ENABLED) {
    return;
}
if (is_file(dirname(__DIR__) . '/.legacy-hidden')) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Legacy functions are hidden. See docs/source-preparation/SOURCE_PREPARATION.md.\n");
        exit(3);
    }
    if (basename(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '') === 'service-worker.js') {
        $_GET['worker'] = '1';
    }
    require dirname(__DIR__) . '/project-start.php';
    exit;
}
