<?php
/** CLI-only tests. Pure routing decisions plus HTTP through the real router.php. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/includes/ita_mode.inc.php';
$checks = 0;
function checkRoute(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

function fetchRoute(string $path, string $method = 'GET'): array
{
    $context = stream_context_create(['http' => [
        'method' => $method, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 5,
    ]]);
    $body = file_get_contents('http://127.0.0.1:18769' . $path, false, $context);
    $headers = $http_response_header ?? [];
    preg_match('/\s(\d{3})\s/', $headers[0] ?? '', $match);
    return ['status' => (int) ($match[1] ?? 0), 'body' => $body, 'headers' => implode("\n", $headers)];
}

$active = ['enabled' => true, 'legacy_enabled' => false];
$restored = ['enabled' => true, 'legacy_enabled' => true];
$flagRoot = sys_get_temp_dir() . '/infracore-flags-' . bin2hex(random_bytes(8));
mkdir($flagRoot, 0700);
try {
    checkRoute(itaModeFlags($flagRoot) === ['enabled' => false, 'legacy_enabled' => false], 'No flags');
    file_put_contents($flagRoot . '/.ita-enabled', 'test');
    checkRoute(itaModeFlags($flagRoot) === $active, 'Enabled marker');
    file_put_contents($flagRoot . '/.ita-legacy-enabled', 'test');
    checkRoute(itaModeFlags($flagRoot) === $restored, 'Restoration marker');
    unlink($flagRoot . '/.ita-enabled');
    checkRoute(itaModeFlags($flagRoot) === ['enabled' => false, 'legacy_enabled' => false], 'Legacy marker alone is inactive');
} finally {
    foreach (['.ita-enabled', '.ita-legacy-enabled'] as $flag) {
        if (is_file($flagRoot . '/' . $flag)) {
            unlink($flagRoot . '/' . $flag);
        }
    }
    rmdir($flagRoot);
}
checkRoute(itaRequestDecision('/admin.php', ['op' => 'ita'], 'GET', $active) === 'shell', 'New module');
checkRoute(itaRequestDecision('/admin.php', ['op' => 'pm'], 'GET', $active) === 'redirect', 'Legacy redirect');
checkRoute(itaRequestDecision('/admin.php', ['op' => 'pm'], 'POST', $active) === 'denied', 'No legacy POST');
checkRoute(itaRequestDecision('/admin.php', ['op' => 'pm'], 'GET', $restored) === 'legacy', 'Legacy flag bypass');
checkRoute(itaRequestDecision('/admin.php', ['op' => 'ita'], 'GET', ['enabled' => false, 'legacy_enabled' => false]) === 'legacy', 'Mode disabled preserves old gate');
checkRoute(itaRequestDecision('/assets/ita/css/../mock/a.json', [], 'GET', $active) === 'not_found', 'Traversal denied');
checkRoute(itaRequestDecision('/%2eenv', [], 'GET', $active) === 'not_found', 'Encoded private file denied');

$response = fetchRoute('/admin.php?op=ita');
checkRoute($response['status'] === 303 && str_contains($response['headers'], 'act=login'), 'Protected shell redirects anonymous user');
$response = fetchRoute('/admin.php?op=ita&act=login');
checkRoute($response['status'] === 200 && !str_contains($response['body'], '{include') && str_contains($response['body'], 'csrf_token'), 'Rendered login template');
checkRoute(str_contains($response['headers'], 'Content-Security-Policy:') && str_contains($response['headers'], 'no-store'), 'Security headers');
foreach (['/', '/admin.php?op=pm', '/index.php?op=estore', '/ajax.php', '/admin.php?op=login'] as $path) {
    $response = fetchRoute($path);
    checkRoute($response['status'] === 303 && str_contains($response['headers'], '/admin.php?op=ita&notice=legacy_hidden'), 'Legacy redirect ' . $path);
}
foreach (['/includes/config.inc.php', '/dung_infracore.sql', '/database/host/phase01_create_ita_tables.sql', '/modules/admin/ita.module.php', '/cron/pm-reminders.php', '/.ita-enabled', '/assets/ita/preview/serve-preview.cjs'] as $path) {
    checkRoute(fetchRoute($path)['status'] === 404, 'Private path blocked ' . $path);
}
checkRoute(fetchRoute('/admin.php?op[]=ita')['status'] === 400, 'Array operation rejected');
checkRoute(fetchRoute('/admin.php?op=pm', 'POST')['status'] === 403, 'Legacy writes denied');
checkRoute(fetchRoute('/admin.php?op=ita', 'POST')['status'] === 403, 'Only explicit auth/access POST routes allowed');
checkRoute(fetchRoute('/assets/ita/css/ita-tokens.css')['status'] === 200, 'CSS loads through router');
checkRoute(fetchRoute('/assets/ita/mock/overview.json')['status'] === 200, 'Mock JSON loads');
$worker = fetchRoute('/service-worker.js');
checkRoute($worker['status'] === 200 && str_contains($worker['body'], 'unregister()'), 'Legacy service worker retires');
echo json_encode(['result' => 'PASS', 'checks' => $checks, 'scope' => 'real PHP router and protected login; no legacy restore E2E']) . PHP_EOL;
