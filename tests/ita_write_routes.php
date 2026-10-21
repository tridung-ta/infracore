<?php
/** Routing regression only: no configuration, session or database access. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/ita_mode.inc.php';
$flags = ['enabled'=>true,'legacy_enabled'=>false];
$checks = 0;
foreach (['ticket-save','license-save','license-key'] as $action) {
    if (itaRequestDecision('/admin.php',['op'=>'ita','act'=>$action],'POST',$flags)!=='shell') {
        throw new RuntimeException('Approved POST blocked: '.$action);
    }
    $checks++;
}
foreach (['tickets','ticket','ticket-technicians','ticket-statistics'] as $action) {
    if (itaRequestDecision('/admin.php',['op'=>'ita','act'=>$action],'GET',$flags)!=='shell') {
        throw new RuntimeException('Approved GET blocked: '.$action);
    }
    $checks++;
}
foreach (['unknown-action','tickets','',null] as $action) {
    if (itaRequestDecision('/admin.php',['op'=>'ita','act'=>$action],'POST',$flags)!=='denied') {
        throw new RuntimeException('Unexpected POST allowed');
    }
    $checks++;
}
if (itaRequestDecision('/admin.php',['op'=>'pm','act'=>'ticket-save'],'POST',$flags)!=='denied') {
    throw new RuntimeException('Legacy POST allowed');
}
$checks++;
echo "PASS: $checks routing checks; no DB.\n";
