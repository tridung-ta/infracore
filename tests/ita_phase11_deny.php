<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_mode.inc.php';
$active=['enabled'=>true,'legacy_enabled'=>false];$checks=0;
$paths=['/tests','/tests/','/tests/run.php','/ARTIFACTS/file.zip','/scripts/check.php','/assets/ita/mock/overview.json',
    '/assets/ita/mock/dashboard.html','/assets/ita/mock/reports.html','/assets/ita/mock/login-preview.html',
    '/ASSETS/ITA/MOCK/overview.json','/%74ests/x.php','/tests%2Fx.php','/assets//ita///mock/data.json',
    '/assets/ita/css/../mock/data.json','/assets/ita/css/../../../../tests/run.php','/tests\\run.php',
    '/%2574ests/run.php','/%2e%2e/tests/x.php','/tests/./x.php','/assets/ita/mock%00/data.json'];
foreach ($paths as $path) {
    foreach (['GET','HEAD','POST'] as $method) {
        if (itaRequestDecision($path,[],$method,$active)!=='not_found') { throw new RuntimeException("Deny $method $path"); }$checks++;
    }
}
foreach (['/assets/ita/css/ita-shell.css','/assets/ita/js/ita-shell.js','/assets/ita/vendor/echarts/6.1.0/echarts.min.js','/assets/fonts/inter/InterVariable.woff2'] as $path) {
    foreach (['GET','HEAD'] as $method) {
        if (itaRequestDecision($path,[],$method,$active)!=='static') { throw new RuntimeException("Runtime $method $path"); }$checks++;
    }
    if (itaRequestDecision($path,[],'POST',$active)!=='denied') { throw new RuntimeException('Static POST'); }$checks++;
}
foreach ([['enabled'=>false,'legacy_enabled'=>false],['enabled'=>true,'legacy_enabled'=>true]] as $flags) {
    if (itaRequestDecision('/tests/x.php',[],'GET',$flags)!=='legacy') { throw new RuntimeException('Restore semantics changed'); }$checks++;
}
// Guards are additive before existing static bypass; this checks ordering, not Apache execution.
$rewrite=file_get_contents(dirname(__DIR__).'/.htaccess');
$deny=strpos($rewrite,'RewriteRule ^(?:tests|artifacts|scripts|assets/ita/mock)(?:/|$) - [F,END,NC]');
$static=strpos($rewrite,'RewriteRule ^assets/ita/(?:css|js|vendor)/');
if ($deny===false || $static===false || $deny>=$static || str_contains($rewrite,'(?:css|js|mock|vendor)')) { throw new RuntimeException('Apache deny/static precedence'); }$checks++;
echo "$checks pure deny/precedence checks passed; no DB, not Apache execution.\n";
