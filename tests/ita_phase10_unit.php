<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function ok10(bool $condition,string $label): void { global $checks;if(!$condition)throw new RuntimeException($label);$checks++; }
function reject10(callable $fn,int $status): void { try{$fn();}catch(ItaHttpError $e){ok10($e->status===$status,'HTTP '.$status);return;}throw new RuntimeException('Expected '.$status); }
class DashboardTestDb extends ItaDb
{
    public array $calls=[];public bool $rollback=false,$failAudit=false,$nativeActive=true;public string $isolation='REPEATABLE-READ';public int $revision=1;
    public array $assets=[],$scores=[],$licenses=[];
    public function __construct() {}
    public function transactionIsolation(): string { return $this->isolation; }
    public function beginTransaction(): void { $this->calls[]=['begin']; }
    public function commit(): void { $this->calls[]=['commit']; }
    public function rollBack(): void { $this->rollback=true;$this->calls[]=['rollback']; }
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        $this->calls[]=[$sql,$types,$params];
        if(str_contains($sql,'ita_user_roles'))return ['role'=>'admin_it','enabled'=>1,'auth_revision'=>$this->revision];
        if(str_contains($sql,'FROM dc_users'))return $this->nativeActive?['id'=>1]:null;
        throw new RuntimeException('Unexpected one '.$sql);
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        if(substr_count($sql,'?')!==strlen($types)||strlen($types)!==count($params))throw new RuntimeException('Binding mismatch');
        $this->calls[]=[$sql,$types,$params];
        if(str_contains($sql,'FROM ita_settings'))return [];
        if(str_starts_with($sql,'WITH cfg'))return $this->scores;
        if(str_contains($sql,'FROM ita_assets a LEFT JOIN'))return $this->assets;
        if(str_contains($sql,'FROM ita_licenses l'))return $this->licenses;
        if(str_contains($sql,'FROM ita_departments'))return [['id'=>10,'name'=>'CNTT']];
        if(str_contains($sql,'ita_user_scopes'))return [['department_id'=>10]];
        throw new RuntimeException('Unexpected all '.$sql);
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        $this->calls[]=[$sql,$types,$params];if($this->failAudit)throw new RuntimeException('Audit fails');return 1;
    }
}
$actor=fn($role,$ids=[])=>new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'fullname'=>'Mock','username'=>'mock'],$ids);
$admin=$actor('admin_it');$manager=$actor('system_manager',[10]);$support=$actor('support_technician',[10]);
reject10(fn()=>ItaDashboardRules::access($support),403);ItaDashboardRules::access($support,true);ok10(true,'Support maintenance allowed');
foreach(['inventory'=>5000,'maintenance'=>1000,'lifecycle'=>1000,'budget'=>1000] as $type=>$limit){ok10(ItaDashboardRules::limit($type,'xlsx')===$limit,'Excel limit');ok10(ItaDashboardRules::limit($type,'pdf')===500,'PDF limit');}
reject10(fn()=>ItaDashboardRules::limit('bad','pdf'),422);reject10(fn()=>ItaDashboardRules::limit('inventory','zip'),422);
foreach(['2022-10-21'=>true,'2022-10-22'=>false,'2021-10-21'=>true] as $date=>$expected){ok10(ItaDashboardRules::shortLife(['purchase_date'=>$date,'lifespan_years'=>5],'2026-10-21')===$expected,'12 month edge');}
ok10(ItaDashboardRules::shortLife(['purchase_date'=>null,'lifespan_years'=>5],'2026-10-21')===null,'Missing purchase');
ok10(ItaDashboardRules::shortLife(['purchase_date'=>'2099-01-01','lifespan_years'=>5],'2026-10-21')===null,'Future purchase');
ok10(ItaMaintenanceSchedule::next('2024-02-29',12)==='2025-02-28','Leap clamp');
$a=['id'=>1,'asset_code'=>'MOCK-001','name'=>'Thiết bị giả','status'=>'maintenance','department_id'=>10,'department_name'=>'CNTT','category_id'=>1,'category_name'=>'Máy tính','purchase_date'=>'2020-01-01','warranty_end'=>'2026-10-25','maintenance_due'=>'2026-10-20','awaiting_ready'=>1];
$alerts=ItaDashboardRules::alerts([$a],[], '2026-10-21');ok10(count($alerts)===3,'Three reasons distinct same asset');ok10($alerts[0]['level']==='critical','Critical first');
ok10(count(ItaDashboardRules::alerts([array_replace($a,['status'=>'retired'])],[],'2026-10-21'))===0,'Retired no alerts');
$license=['id'=>2,'software_name'=>'Giấy phép giả','expire_date'=>'2026-10-28','used_seats'=>2,'total_seats'=>1,'status'=>'active'];
ok10(count(ItaDashboardRules::alerts([],[$license],'2026-10-21'))===2,'License reasons distinct');
ok10(ItaDashboardRules::alerts([],[$license],'2026-10-21')[1]['level']==='critical','Seven-day expiry critical');
$s=['id'=>1,'repair_count'=>3,'tier'=>'watch','score_raw'=>'0.7996'];
$g=ItaDashboardRules::groups([$a], [1=>$s],'category')[0];ok10($g['failed_assets']===1&&$g['analyzed']===1&&$g['replace']===0,'Distinct asset and raw tier');
$s['repair_count']=4;ok10(ItaDashboardRules::groups([$a],[1=>$s],'category')[0]['failed_assets']===1,'More tickets no denominator inflation');
ok10(ItaDashboardRules::paginate([],99)['page']===1,'Empty page');ok10(ItaDashboardRules::paginate(range(1,21),99)['page']===2,'Clamp last page');
$db=new DashboardTestDb();$db->assets=[$a];$db->licenses=[$license];
$score=$s+['asset_code'=>$a['asset_code'],'name'=>$a['name'],'age_days'=>2485,'lifespan_years'=>'5','purchase_date'=>$a['purchase_date'],'purchase_price'=>'100','status'=>'maintenance'];$db->scores=[$score];
$asOf='2026-10-21T10:00:00+07:00';$dao=new ItaDashboardObjects($db,$manager,$asOf);
reject10(fn()=>$dao->assets(['department_id'=>'11']),403);
$plans=$dao->queryPlans(['department_id'=>'10']);ok10(str_contains($plans['assets']['sql'],'a.department_id IN (?)'),'Scope before aggregation');ok10(str_contains($plans['assets']['sql'],'LIMIT 5001'),'No silent cut');
foreach($plans as $p){ok10(substr_count($p['sql'],'?')===count($p['params']),'Query binds');}
$dao->licenses([]);$licenseSql=end($db->calls);ok10(str_contains($licenseSql[0],'EXISTS')&&!str_contains($licenseSql[0],'license_key'),'Manager scoped license without key');
$dashboard=$dao->dashboard([]);ok10($dashboard['as_of']===$asOf&&$dashboard['summary']['failed_assets']===1,'Shared server asof');ok10($dashboard['summary']['replace']===0,'Raw 0.7996 no replace');
$filtered=$dao->alerts(['level'=>'critical'],1);ok10($filtered['active_total']===5&&$filtered['total']===3,'Bell independent of list filter');
echo "PASS: $checks dashboard pure/scripted checks; no DB.\n";
