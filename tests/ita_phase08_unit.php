<?php
/** Pure/scripted lifecycle tests. Never creates/connects a database or loads private config. */
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function ok08(bool $ok,string $label): void { global $checks;if(!$ok)throw new RuntimeException($label);$checks++; }
function close08(float $value,float $expected,string $label): void { ok08(abs($value-$expected)<1e-12,$label); }
function reject08(callable $fn,int $status): void { try{$fn();}catch(ItaHttpError $e){ok08($e->status===$status,'HTTP '.$status);return;}throw new RuntimeException('Expected rejection'); }
$config=ItaLifecycleRules::settings([]);$v=$config['values'];
ok08($config['using_defaults']&&count($config['default_keys'])===5,'Absent settings use defaults');
ok08(!ItaLifecycleRules::settings($v)['using_defaults'],'All existing settings');
reject08(fn()=>ItaLifecycleRules::settings(['lifecycle_weight_age'=>'oops']),422);
reject08(fn()=>ItaLifecycleRules::settings(['lifecycle_weight_age'=>null]),422);
reject08(fn()=>ItaLifecycleRules::settings(['lifecycle_weight_age'=>'0.6']),422);
reject08(fn()=>ItaLifecycleRules::settings(['lifecycle_watch_threshold'=>'0.8']),422);
$base=['age_years'=>5,'lifespan_years'=>5,'purchase_price'=>'100','repair_count'=>0,'repair_cost'=>'0','status'=>'in_use'];
close08(ItaLifecycleRules::score($base,$v)['score_raw'],.5,'Age equals lifespan');
close08(ItaLifecycleRules::score(array_replace($base,['repair_count'=>3]),$v)['score_raw'],.8,'3 repairs gives exact threshold');
close08(ItaLifecycleRules::score(array_replace($base,['repair_count'=>4]),$v)['score_raw'],.8,'4 repairs capped component');
ok08(ItaLifecycleRules::score(array_replace($base,['repair_count'=>3]),$v)['tier']==='replace','Exact .8 replace');
$r=ItaLifecycleRules::score(array_replace($base,['repair_count'=>1]),$v);close08($r['score_raw'],.6,'Exact .6 score');ok08($r['tier']==='watch','Exact .6 watch');
close08(ItaLifecycleRules::score(array_replace($base,['status'=>'broken','purchase_price'=>null]),$v)['score_raw'],.7,'Broken no price');
foreach ([null,'0','0.00'] as $price)ok08(ItaLifecycleRules::score(array_replace($base,['purchase_price'=>$price]),$v)['tier']==='missing','Missing/zero price');
foreach ([null,0] as $life)ok08(ItaLifecycleRules::score(array_replace($base,['lifespan_years'=>$life]),$v)['tier']==='missing','Missing/zero life');
ok08(ItaLifecycleRules::age('2026-10-22','2026-10-21')===null,'Future purchase');
ok08(ItaLifecycleRules::age(null,'2026-10-21')===null,'Missing purchase');
close08(ItaLifecycleRules::age('2022-10-21','2026-10-21'),4,'Leap-day-aware days');
$r=ItaLifecycleRules::score(array_replace($base,['age_years'=>10,'repair_count'=>4,'status'=>'broken']),$v);close08($r['score_raw'],1.5,'Score above 1');
ok08(ItaLifecycleRules::tier(.5999,$v)==='normal'&&ItaLifecycleRules::tier(.7999,$v)==='watch','Raw threshold comparisons');
ok08(ItaLifecycleRules::display('0.799999999999',$v)==='0.800','Exactly 3 displayed decimals; classification uses raw');
ok08(ItaLifecycleRules::display('1.5123456',$v)==='1.512','3-decimal display');
$window=ItaLifecycleRules::window('2024-02-29');ok08($window['start_date']==='2023-02-28','12 calendar months clamp leap day');
ok08($window['start_utc']==='2023-02-27 17:00:00'&&$window['end_utc']==='2024-02-29 17:00:00','VN UTC window');
$w=ItaLifecycleRules::window('2026-10-21');
$within=fn($stamp)=>$stamp>=$w['start_utc']&&$stamp<$w['end_utc'];
ok08($within($w['start_utc'])&&!$within($w['end_utc']),'Inclusive start, exclusive end');
ok08(!$within('2025-10-20 16:59:59')&&$within('2026-10-21 16:59:59'),'Boundary seconds');
class Db08 extends ItaDb {
 public array $calls=[],$rows=[];public ?array $asset=['id'=>7,'department_id'=>10];
 public function __construct() {}
 public function fetchAll(string $sql,string $types='',array $params=[]): array { $this->calls[]=[$sql,$types,$params];if(str_contains($sql,'FROM ita_settings'))return [];if(str_contains($sql,'FROM graded')&&str_contains($sql,'LIMIT 20'))return $this->rows;return []; }
 public function fetchOne(string $sql,string $types='',array $params=[]): ?array { $this->calls[]=[$sql,$types,$params];return str_contains($sql,'SELECT id,department_id')?$this->asset:['n'=>0,'total'=>0,'failed_assets'=>0,'age_known'=>0,'average_age_years'=>null,'missing_scores'=>0]; }
}
$actor=fn($role,$scope=[])=>new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'username'=>'QA','fullname'=>'QA'],$scope);
$db=new Db08();reject08(fn()=>new ItaLifecycleObjects($db,$actor('support_technician')),403);ok08(!$db->calls,'Denied before queries');
$objects=new ItaLifecycleObjects($db,$actor('system_manager',[10]),'2026-10-21');
$plans=$objects->queryPlans(['q'=>"QA%_'",'tier'=>'watch']);
foreach($plans as $name=>$p){ok08(substr_count($p['sql'],'?')===count($p['params'])&&strlen($p['types'])===count($p['params']),'Prepared params '.$name);ok08(str_contains($p['sql'],'a.department_id IN (?)'),'Scoped '.$name);ok08(!str_contains($p['sql'],'ita_ticket_events'),'No event multiply '.$name);ok08(str_contains($p['sql'],"t.type='repair'")&&str_contains($p['sql'],'t.opened_at>=? AND t.opened_at<?'),'Repair cohort '.$name);}
ok08(str_contains($plans['list']['sql'],'ORDER BY score_raw IS NULL,score_raw DESC,id ASC'),'Raw score sorting, stable ties');
ok08(str_contains($plans['category']['sql'],'GROUP BY category_id,category_name'),'Category aggregation');
ok08(str_contains($plans['department']['sql'],'GROUP BY department_id,department_name'),'Department aggregation');
reject08(fn()=>$objects->queryPlans(['department_id'=>'11']),403);
reject08(fn()=>$objects->queryPlans(['tier'=>'bad']),422);
reject08(fn()=>$objects->detail(7),404);$db->asset=['id'=>8,'department_id'=>11];reject08(fn()=>$objects->detail(8),403);
$empty=new ItaLifecycleObjects(new Db08(),$actor('system_manager',[]),'2026-10-21');ok08(str_contains($empty->queryPlans([])['count']['sql'],'AND 1=0'),'Empty scope no assets');
$admin=new ItaLifecycleObjects(new Db08(),$actor('admin_it'),'2026-10-21');ok08(!str_contains($admin->queryPlans([])['count']['sql'],'a.department_id IN'),'Admin store scope');
$emptyList=$admin->listing([],99);ok08($emptyList['page']===1&&$emptyList['total']===0&&$emptyList['config']['using_defaults'],'Empty DAO list/default metadata');
$fixture=json_decode(file_get_contents(dirname(__DIR__).'/assets/ita/mock/lifecycle.json'),true,512,JSON_THROW_ON_ERROR);
$successDb=new Db08();$successDb->rows=[$fixture['rows'][0]];
$success=new ItaLifecycleObjects($successDb,$actor('system_manager',[10]),'2026-10-21');
$detail=$success->detail(7)['asset'];ok08($detail['score_raw']===$fixture['rows'][0]['score_raw'],'DTO preserves unrounded score');
ok08(preg_match('/^[0-9]+\.[0-9]{3}$/D',$detail['score_display'])===1,'DTO score exactly3 decimals');
require dirname(__DIR__).'/includes/ita_mode.inc.php';
foreach(['lifecycle','lifecycle-detail'] as $action){
 ok08(itaRequestDecision('/admin.php',['op'=>'ita','act'=>$action],'GET',['enabled'=>true,'legacy_enabled'=>false])==='shell','GET accepted by outer gate');
 ok08(itaRequestDecision('/admin.php',['op'=>'ita','act'=>$action],'POST',['enabled'=>true,'legacy_enabled'=>false])==='denied','POST denied by outer gate');
}
// Same ticket ID reopened: aggregation source has one ticket row; events are not a source.
$tickets=[['id'=>9,'type'=>'repair','opened_at'=>'2026-10-21 01:00:00','status'=>'failed']];
$count=fn($rows)=>count(array_filter($rows,fn($t)=>$t['type']==='repair'&&$within($t['opened_at'])));
$before=$count($tickets);$tickets[0]['status']='pending';ok08($count($tickets)===$before&&$before===1,'Reopened ticket remains one row/count');
echo "$checks pure/scripted lifecycle checks passed; no DB.\n";
