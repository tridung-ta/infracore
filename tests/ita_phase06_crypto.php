<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../classes/security/itahttperror.class.php';
require_once __DIR__.'/../classes/security/itainput.class.php';
require_once __DIR__.'/../classes/dao/itauserobjectinfo.class.php';
require_once __DIR__.'/../classes/services/italicensecrypto.class.php';
require_once __DIR__.'/../classes/services/italicenserules.class.php';
$checks = 0;
function check(bool $value): void { global $checks; if (!$value) { throw new RuntimeException('Failed check '.($checks+1)); } $checks++; }
function denied(callable $fn, int $status): void { try { $fn(); } catch (ItaHttpError $e) { check($e->status===$status); return; } throw new RuntimeException('Expected rejection'); }
$crypto = new ItaLicenseCrypto(base64_encode(random_bytes(32)));
$context = '1:12';
$cipher = $crypto->encrypt('KEY-ĐỎ-0001',$context);
check($crypto->decrypt($cipher,$context)==='KEY-ĐỎ-0001');
denied(fn()=>(new ItaLicenseCrypto(base64_encode(random_bytes(32))))->decrypt($cipher,$context),503);
denied(fn()=>$crypto->decrypt($cipher,'2:12'),503);
$bytes = base64_decode(substr($cipher,3));
$bytes[29] = chr(ord($bytes[29])^1);
denied(fn()=>$crypto->decrypt('v1:'.base64_encode($bytes),$context),503);
denied(fn()=>new ItaLicenseCrypto('invalid'),503);
$nonces = [];
for ($i=0;$i<1000;$i++) { $c=$crypto->encrypt('same',$context); $nonces[]=substr(base64_decode(substr($c,3)),0,12); }
check(count(array_unique($nonces))===1000);
foreach (['admin_it','system_manager','support_technician'] as $role) {
    $actor = new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'fullname'=>'Test','username'=>'test'],[1]);
    if ($role==='support_technician') { denied(fn()=>ItaLicenseRules::manage($actor),403); } else { ItaLicenseRules::manage($actor); check(true); }
    if ($role!=='admin_it') { denied(fn()=>$actor->requireAdmin(),403); denied(fn()=>ItaLicenseRules::excess($actor,3,2,['confirm_overage'=>true,'override_reason'=>'Test']),403); }
    else { denied(fn()=>ItaLicenseRules::excess($actor,3,2,[]),422); check(ItaLicenseRules::excess($actor,3,2,['confirm_overage'=>true,'override_reason'=>'Test'])==='Test'); }
    check(ItaLicenseRules::excess($actor,2,2,[])===null);
}
foreach ([31=>'none',30=>'warning',8=>'warning',7=>'critical',0=>'critical',-1=>'critical'] as $days=>$level) {
    $date = (new DateTimeImmutable('2026-10-20'))->modify(($days>=0?'+':'').$days.' days')->format('Y-m-d');
    $alert = ItaLicenseRules::alert($date,2,2,'2026-10-20');
    check($alert['expiry_level']===$level && $alert['days_remaining']===$days);
}
check(ItaLicenseRules::alert(null,3,2)['overallocated']);
check(!ItaLicenseRules::alert(null,2,2)['overallocated']);
$valid=['software_name'=>'QA','license_type'=>'per_device','status'=>'active','total_seats'=>'2'];
foreach ([['total_seats'=>'0'],['cost'=>'-1'],['start_date'=>'2026-02-30'],['start_date'=>'2026-12-01','expire_date'=>'2026-11-01'],['software_name'=>''],['license_type'=>'other']] as $bad) {
    denied(fn()=>ItaLicenseRules::fields(array_replace($valid,$bad)),422);
}
echo "$checks pure crypto/role/boundary checks passed; no DB.\n";
