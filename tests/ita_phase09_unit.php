<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
require __DIR__.'/fixtures/ita_forecast_db.php';
$checks=0;
function ok09(bool $ok,string $label): void { global $checks;if(!$ok)throw new RuntimeException($label);$checks++; }
function reject09(callable $fn,int $status): void { try{$fn();}catch(ItaHttpError $e){ok09($e->status===$status,'HTTP '.$status);return;}throw new RuntimeException('Expected rejection'); }
$actor=fn($role,$scope=[])=>new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'fullname'=>'Mock','username'=>'mock'],$scope);
$admin=$actor('admin_it');$db=new ForecastTestDb();$dao=new ItaForecastObjects($db,$admin);$service=new ItaForecastService($db,$admin);
ok09(ItaForecastMoney::total(['9999999999999999.99','0.01'])==='10000000000000000.00','Exact large sum');
ok09(ItaForecastMoney::format(ItaForecastMoney::divide('15',10))==='0.02','HALF_UP');
ok09(ItaForecastMoney::divide('1000001',1000000,'ceil')==='2','Ceil procurement');
ok09(ItaForecastMoney::multiply('1234567890123456','1000')==='1234567890123456000','Exact product');
foreach (['-1','1e8','NaN',[],true] as $bad) { reject09(fn()=>ItaForecastMoney::units($bad),422); }
reject09(fn()=>ItaForecastRules::parameter('100.000001',100),422);
reject09(fn()=>ItaForecastRules::parameter('-1',100),422);
reject09(fn()=>new ItaForecastObjects($db,$actor('support_technician')),403);
reject09(fn()=>(new ItaForecastService($db,$actor('system_manager',[10])))->configure([]),403);
$manager=new ItaForecastObjects($db,$actor('system_manager',[10]));
reject09(fn()=>$manager->calculate(['year'=>date('Y'),'department_id'=>11]),403);
reject09(fn()=>$dao->calculate(['year'=>2201]),422);
$plans=$manager->queryPlans(['department_id'=>10]);
foreach (['assets','history'] as $key) {
 $p=$plans[$key];ok09(substr_count($p['sql'],'?')===count($p['params'])&&strlen($p['types'])===count($p['params']),'Binding '.$key);
 ok09(str_contains($p['sql'],'a.department_id IN (?)'),'Scope '.$key);ok09(!str_contains($p['sql'],'ita_ticket_events'),'No event multiply '.$key);
}
ok09(str_contains($plans['history']['sql'],"t.status IN ('resolved','failed')"),'Closed historical cohort');
ok09(str_contains($plans['history']['sql'],'t.opened_at>=? AND t.opened_at<?'),'Half-open history window');
$group=['department_id'=>10,'department_name'=>'CNTT','category_id'=>1,'category_name'=>'Máy tính','asset_count'=>2,'replacement_count'=>1,'unknown_replacement'=>0,'history_count'=>2,'history_missing'=>0,'history_cents'=>'900000000','missing'=>[]];
$config=['price'=>'20000000.00','units_per_person'=>'1.5','maintenance_percent'=>null];
$r=ItaForecastRules::row($group,$config,3);$sum=ItaForecastRules::summary([$r]);
ok09($r['replacement']==='20000000.00'&&$r['maintenance']==='3000000.00'&&$r['growth']==='100000000.00'&&$r['growth_quantity']==='5','Hand-calculated components');
ok09($sum['contingency']==='12300000.00'&&$sum['total']==='135300000.00'&&$sum['complete'],'Hand-calculated total');
$missing=ItaForecastRules::row($group,array_replace($config,['price'=>null,'units_per_person'=>null]),3);
ok09($missing['replacement']===null&&$missing['growth']===null&&!ItaForecastRules::summary([$missing])['complete'],'Missing is not zero');
$zero=ItaForecastRules::row($group,array_replace($config,['price'=>'0']),3);ok09($zero['replacement']==='0.00'&&$zero['growth']==='0.00','Explicit zero price known');
$g=array_replace($group,['history_count'=>0,'history_cents'=>'0']);
ok09(ItaForecastRules::row($g,$config,0)['maintenance']===null,'Missing maintenance not guessed');
ok09(ItaForecastRules::row($g,array_replace($config,['maintenance_percent'=>'10']),0)['maintenance']==='4000000.00','Explicit fallback');
ok09(ItaForecastRules::row(array_replace($group,['history_cents'=>'0']),$config,0)['maintenance']==='0.00','Zero historical cost known');
ok09(ItaForecastRules::row($group,$config,-1)['growth']===null,'No plan is not zero headcount');
ok09(ItaForecastRules::replacement(['tier'=>'replace','purchase_date'=>'2010-01-01','lifespan_years'=>5],(int)date('Y'))===true,'OR eligibility once');
$current=$dao->calculate(['year'=>date('Y')]);ok09($current['rows'][0]['replacement_count']===1,'Dual eligible asset counted once');
ok09($current['rows'][0]['growth']===null,'Absent plan incomplete');
$cfg=$dao->configuration();$service->configure(['version'=>$cfg['version'],'category_id'=>1,'price'=>'20000000.00','units_per_person'=>'1.5','maintenance_percent'=>'10']);
$input=['year'=>date('Y'),'department_id'=>10,'planned_headcount_increase'=>3,'note'=>'=SUM(A1:A2)'];
$saved=$service->save($input);ok09($saved['saved']===true&&$saved['plan_id']===1,'Saved metadata overrides raw false');
ok09($saved['rows'][0]['asset_inputs'][0]['score_raw']==='1.2'&&$saved['rows'][0]['asset_inputs'][0]['history']['cost']==='9000000.00','Snapshot freezes asset and history inputs');
$before=$saved['rows'];$db->categories[0]['default_replacement_cost']='99999999.00';
ok09($dao->saved(1)['rows']===$before,'Price change never changes snapshot');
reject09(fn()=>$service->save($input),409);
$recalc=$input+['version'=>$saved['version'],'confirm_recalculate'=>true];$updated=$service->save($recalc);
ok09($updated['rows']!==$before,'Explicit recalculate changes draft');
reject09(fn()=>$service->auditExport($saved),409);
$filtered=$dao->saved(1,1);ok09(count($filtered['rows'])===1&&$filtered['saved'],'Saved category filter');
reject09(fn()=>$dao->saved(1,99),404);
$db->plans[1]['status']='finalized';$old=$db->plans[1];reject09(fn()=>$service->save($input+['version'=>ItaForecastObjectInfo::version($old),'confirm_recalculate'=>true]),409);
ok09($db->plans[1]===$old,'Finalized immutable');
$db->plans[1]['calculation_snapshot_json']='{"schema_version":1,"year":'.date('Y').',"rows":[]}';reject09(fn()=>$dao->saved(1),409);
$badDb=new ForecastTestDb();$badDb->auditFails=true;
try{(new ItaForecastService($badDb,$admin))->save($input);throw new LogicException('Expected audit failure');}catch(RuntimeException $e){ok09($badDb->rolledBack&&!$badDb->plans,'Scripted audit failure rollback');}
$managerDb=new ForecastTestDb();$managerDb->role='system_manager';
$managerService=new ItaForecastService($managerDb,$actor('system_manager',[10]));
ok09($managerService->save($input)['saved']===true,'Manager saves in server scope');
reject09(fn()=>$managerService->save(array_replace($input,['department_id'=>11])),403);
reject09(fn()=>(new ItaForecastObjects($managerDb,$actor('system_manager',[11])))->saved(1),403);
$badDb2=new ForecastTestDb();$badDb2->auditFails=true;$originalPrice=$badDb2->categories[0]['default_replacement_cost'];
try{(new ItaForecastService($badDb2,$admin))->configure(['category_id'=>1,'version'=>(new ItaForecastObjects($badDb2,$admin))->configuration()['version'],'price'=>'1']);throw new LogicException('Expected audit failure');}
catch(RuntimeException $e){ok09($badDb2->rolledBack&&$badDb2->categories[0]['default_replacement_cost']===$originalPrice&&!$badDb2->settings,'Config rollback double');}
$tooLarge=$updated;$tooLarge['rows']=array_fill(0,ItaForecastRules::MAX_ROWS+1,$updated['rows'][0]);reject09(fn()=>ItaForecastExport::table($tooLarge),422);
$invalid=$updated;$invalid['summary']['total']='0.00';$badPlan=$old;$badPlan['calculation_snapshot_json']=json_encode($invalid);reject09(fn()=>ItaForecastObjectInfo::snapshot($badPlan),409);
ok09(ItaForecastRules::replacement(['tier'=>'missing','purchase_date'=>'2199-01-01','lifespan_years'=>1,'score_raw'=>null],2200)===null,'Future purchase unknown even at forecast horizon');
foreach ($db->calls as [$sql,$types,$params]) { ok09(substr_count($sql,'?')===count($params)&&strlen($types)===count($params),'Prepared call arity'); }
require dirname(__DIR__).'/includes/ita_mode.inc.php';
foreach (['forecast-plan-save','forecast-config-save','forecast-export'] as $action) { ok09(itaRequestDecision('/admin.php',['op'=>'ita','act'=>$action],'POST',['enabled'=>true,'legacy_enabled'=>false])==='shell','Outer POST allowlist'); }
echo "$checks pure/scripted forecast checks passed; no DB.\n";
