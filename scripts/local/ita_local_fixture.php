<?php
/** Local-only fixture CLI. No app configuration loader, migration or reset. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/localfixture.class.php';
$options = getopt('', ['action:', 'db:', 'config:', 'ack:', 'execute']);
try {
    $action = $options['action'] ?? 'plan';
    if ($action === 'plan') {
        echo "PLAN ONLY; no connection. seed: 4 test users / 2 departments / 6 assets / historical tickets. load: total 5000 assets.\n";
        exit;
    }
    LocalFixture::authorize($options);
    $config = LocalFixture::configuration((string) ($options['config'] ?? ''), $options['db']);
    // The sole connection point; authorization and endpoint checks must precede it.
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli($config['host'], $config['user'], $config['password'], $config['name'], $config['port']);
    $db->set_charset('utf8mb4');
    $db->query("SET SESSION time_zone='+00:00'");
    $db->query("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
    $fixture = new LocalFixture($db, $config);
    $fixture->run($action);
    echo "Completed local action: $action. Record actual counts separately; not host acceptance.\n";
} catch (Throwable $error) {
    // Never print credentials, connection exception text or SQL values.
    fwrite(STDERR, 'Local fixture stopped: ' . get_class($error) . '; code=' . $error->getCode() . ". See LOCAL_RUN.md; do not retry/reset blindly.\n");
    exit(1);
}
