<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
require __DIR__.'/fixtures/ita_forecast_db.php';
$actor=new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>'admin_it','auth_revision'=>1,'fullname'=>'Mock','username'=>'mock']);
$db=new ForecastTestDb();$service=new ItaReportService($db,$actor,str_repeat('t',32));$checks=0;
$check=function(bool $ok,string $label) use (&$checks){if(!$ok)throw new RuntimeException($label);$checks++;};
$input=['report'=>'budget','year'=>(string)ItaForecastRules::currentYear(),'department_id'=>'10'];$asOf='2026-10-21T10:00:00+07:00';
$current=$service->document($input,$asOf);
$check($current['budget']['as_of']===$asOf,'Current budget uses request timestamp, not separate now');
$sign=new ReflectionMethod(ItaReportService::class,'signature');
$first=$sign->invoke($service,$current,$input);$second=$sign->invoke($service,$service->document($input,$asOf),$input);
$check($first===$second,'Unchanged budget token stable');
$snapshot=(new ItaForecastObjects($db,$actor))->calculate($input,3);
$db->plans[1]=['id'=>1,'store_id'=>1,'department_id'=>10,'year'=>(int)$input['year'],'planned_headcount_increase'=>3,'note'=>'Mock','status'=>'finalized','calculation_snapshot_json'=>json_encode($snapshot,JSON_THROW_ON_ERROR)];
$savedInput=$input+['plan_id'=>'1'];$saved=$service->document($savedInput,$asOf);
$check($saved['budget']['saved']&&$saved['budget']['status']==='finalized','Finalized snapshot read-only source');
$check($saved['budget']['as_of']===$snapshot['as_of'],'Saved timestamp preserved');
$before=$sign->invoke($service,$saved,$savedInput);$db->categories[0]['default_replacement_cost']='30000000.00';
$after=$service->document($savedInput,$asOf);$check($before===$sign->invoke($service,$after,$savedInput),'Price changes do not rewrite saved snapshot');
$changed=$service->document($input,$asOf);$check($first!==$sign->invoke($service,$changed,$input),'Current budget changes invalidate token');
try{$service->document(array_replace($savedInput,['department_id'=>'11']),$asOf);throw new RuntimeException('Mismatch allowed');}catch(ItaHttpError $e){$check($e->status===422,'Snapshot mismatched filter refused');}
echo "PASS: $checks budget report scripted checks; no DB.\n";
