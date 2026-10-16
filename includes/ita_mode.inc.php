<?php
/** File-based flags shared by PHP and Apache rewrite rules. */
function itaModeFlags(string $rootPath): array
{
    $enabled = is_file($rootPath . '/.ita-enabled');
    return [
        'enabled' => $enabled,
        'legacy_enabled' => $enabled && is_file($rootPath . '/.ita-legacy-enabled'),
    ];
}

/** Decide before any legacy session or database bootstrap. */
function itaRequestDecision(string $path, array $query, string $method, array $flags): string
{
    if (!$flags['enabled'] || $flags['legacy_enabled']) {
        return 'legacy';
    }
    if (!in_array($method, ['GET', 'HEAD', 'POST'], true)) {
        return 'denied';
    }
    $path = rawurldecode($path);
    if (preg_match('~(?:^|/)(?:\.[^/]+|includes|classes|modules|database|docs|templates|templates_c|cron)(?:/|$)~i', $path)
        || preg_match('~\.(?:sql|ini|bak|log|env|key|pem|cjs)$~i', $path)
        || str_contains($path, "\0") || str_contains($path, '\\')) {
        return 'not_found';
    }
    if (preg_match('~^/assets/ita/(?:css|js|mock|vendor)/[a-zA-Z0-9_./-]+\.(?:css|js|json|woff2)$~D', $path)
        || $path === '/assets/fonts/inter/InterVariable.woff2') {
        return $method==='POST' ? 'denied' : (str_contains($path, '..') ? 'not_found' : 'static');
    }
    if ($path === '/service-worker.js') {
        return $method==='POST' ? 'denied' : 'worker';
    }
    if (isset($query['op']) && !is_string($query['op'])) {
        return 'bad_request';
    }
    if ($path === '/admin.php' && ($query['op'] ?? '') === 'ita') {
        if ($method==='POST' && !in_array($query['act'] ?? null, ['login','logout','access'], true)) {
            return 'denied';
        }
        return 'shell';
    }
    if ($method==='POST') {
        return 'denied';
    }
    return 'redirect';
}

$itaFlags = itaModeFlags(dirname(__DIR__));
if (!defined('ITA_ENABLED')) {
    define('ITA_ENABLED', $itaFlags['enabled']);
}
if (!defined('ITA_LEGACY_UI_ENABLED')) {
    define('ITA_LEGACY_UI_ENABLED', $itaFlags['legacy_enabled']);
}
