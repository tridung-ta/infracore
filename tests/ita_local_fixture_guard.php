<?php
// Pure guard tests: no mysqli constructed, no config file opened, no DB.
require_once dirname(__DIR__) . '/scripts/local/localfixture.class.php';
$checks = 0;
function check(bool $ok): void { global $checks; $checks++; if (!$ok) { throw new RuntimeException('Guard assertion failed: '.$checks); } }
function rejected(callable $run): void { try { $run(); } catch (LogicException) { check(true); return; } check(false); }
$name = 'infracore_local_uat';
$options = ['action'=>'seed','db'=>$name,'execute'=>false,'ack'=>'Tôi xác nhận cho chạy trên DB local '.$name];
LocalFixture::authorize($options); check(true); // getopt flag presence is authorization, not its value.
foreach (['db'=>'dung_infracore','ack'=>'yes','action'=>'reset'] as $key=>$value) {
    rejected(fn()=>LocalFixture::authorize(array_replace($options, [$key=>$value])));
}
$missing=$options; unset($missing['execute']); rejected(fn()=>LocalFixture::authorize($missing));
foreach (['infracore_local_../x','infracore_local_A','other','infracore_local_','infracore_local_x;DROP'] as $bad) {
    rejected(fn()=>LocalFixture::authorize(array_replace($options,['db'=>$bad,'ack'=>'Tôi xác nhận cho chạy trên DB local '.$bad])));
}
$c=['purpose'=>'infracore-local-fixture-v1','local_only'=>true,'no_tunnel'=>true,'connection_role'=>'setup',
    'name'=>$name,'host'=>'localhost','port'=>3306,'user'=>'fixture','password'=>'fake-only','test_password'=>'TEST-only-123456',
    'test_license_key'=>base64_encode(str_repeat('x',32))];
check(LocalFixture::validateConfiguration($c,$name)['host']==='127.0.0.1');
foreach (['remote.example','0.0.0.0','127.0.0.2','localhost:3306','127.0.0.1.example',' LOCALHOST',''] as $bad) {
    rejected(fn()=>LocalFixture::validateConfiguration(array_replace($c,['host'=>$bad]),$name));
}
foreach (['local_only'=>false,'no_tunnel'=>false,'connection_role'=>'runtime','name'=>'dung_infracore',
    'port'=>0,'purpose'=>'host','test_password'=>'short','test_license_key'=>'bad'] as $key=>$value) {
    rejected(fn()=>LocalFixture::validateConfiguration(array_replace($c,[$key=>$value]),$name));
}
foreach (['127.0.0.1','::1'] as $host) { check(LocalFixture::validateConfiguration(array_replace($c,['host'=>$host]),$name)['host']===$host); }
check(count(LocalFixture::TABLES)===16);
$entry=file_get_contents(dirname(__DIR__).'/scripts/local/ita_local_fixture.php');
check(strpos($entry,'LocalFixture::authorize') < strpos($entry,'new mysqli'));
check(!str_contains($entry,'ItaDb::configuration'));
$source=file_get_contents(dirname(__DIR__).'/scripts/local/localfixture.class.php');
check(str_contains($source,'$i<=4994'));
check(!str_contains($source,'getenv('));
echo "{$checks} pure guard checks passed; no DB/config IO.\n";
