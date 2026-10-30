<?php
/** Scripted persistence only; never loads private configuration or connects to MySQL. */
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function ok06(bool $ok): void { global $checks; if (!$ok) throw new RuntimeException('Check '.($checks+1).' failed'); $checks++; }
function rejects06(callable $fn,int $status): void { try { $fn(); } catch (ItaHttpError $e) { ok06($e->status===$status); return; } throw new RuntimeException('Expected '.$status); }
function actor06(string $role='admin_it'): ItaUserObjectInfo { return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'username'=>'qa','fullname'=>'QA'],[10]); }
class Db06 extends ItaDb
{
    public array $row=['id'=>7,'store_id'=>1,'software_name'=>'QA <script>','vendor'=>'QA','license_key_enc'=>null,'license_type'=>'per_device','total_seats'=>2,'start_date'=>null,'expire_date'=>null,'cost'=>null,'status'=>'active','created_by'=>111,'created_at'=>'2026-01-01 00:00:00','updated_at'=>'2026-01-01 00:00:00'];
    public int $used=0;
    public array $writes=[];
    public array $reads=[];
    public bool $failAudit=false;
    public bool $rollback=false;
    public int $attempts=0;
    public string $role='admin_it';
    public int $department=10;
    public int $revision=1;
    private array $snapshot=[];
    public function __construct() {}
    public function beginTransaction(): void { $this->snapshot=[$this->row,$this->used,$this->writes,$this->attempts]; }
    public function commit(): void {}
    public function rollBack(): void { [$this->row,$this->used,$this->writes,$this->attempts]=$this->snapshot;$this->rollback=true; }
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        ok06(strlen($types)===count($params));$this->reads[]=[$sql,$params];
        if(str_contains($sql,'JOIN ita_user_roles'))return ['id'=>111,'username'=>'qa','fullname'=>'QA','role'=>$this->role,'auth_revision'=>$this->revision];
        if(str_contains($sql,'ita_user_roles'))return ['role'=>$this->role,'enabled'=>1,'auth_revision'=>$this->revision];
        if(str_contains($sql,'ita_login_limits'))return ['attempts'=>$this->attempts,'age'=>1];
        if(str_contains($sql,'password,password_hash'))return ['password'=>md5('qa-password'),'password_hash'=>''];
        if(str_contains($sql,'COUNT(*)'))return ['n'=>$this->used];
        if(str_contains($sql,'LAST_INSERT_ID'))return ['id'=>7];
        if(str_contains($sql,'ita_licenses'))return $params[0]===1&&$params[1]===7?$this->row:null;
        if(str_contains($sql,'ita_license_allocations'))return ['id'=>8,'asset_id'=>9,'user_id'=>null,'department_id'=>null,'released_at'=>null];
        if(str_contains($sql,'ita_assets'))return ['department_id'=>$this->department,'status'=>'in_use','is_deleted'=>0];
        if(str_contains($sql,'ita_departments')||str_contains($sql,'dc_users'))return ['id'=>9];
        throw new RuntimeException('Unexpected read: '.$sql);
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        ok06(strlen($types)===count($params));$this->reads[]=[$sql,$params];
        if(str_contains($sql,'ita_user_scopes'))return [['department_id'=>10]];
        if(str_contains($sql,'GROUP BY level'))return [['level'=>'critical','n'=>2],['level'=>'warning','n'=>1]];
        if(str_contains($sql,') q WHERE'))return [['id'=>7,'software_name'=>'QA','expire_date'=>null,'status'=>'active','total_seats'=>2,'used_seats'=>3,'level'=>'critical']];
        if(str_contains($sql,'FROM ita_licenses'))return [$this->row+['used_seats'=>$this->used]];
        if(str_contains($sql,'ita_license_allocations'))return [];
        throw new RuntimeException('Unexpected list: '.$sql);
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        ok06(strlen($types)===count($params));
        if(str_contains($sql,'ita_audit_logs')&&$this->failAudit)throw new RuntimeException('QA audit unavailable');
        $this->writes[]=[$sql,$params];
        if(str_contains($sql,'attempts=attempts+1'))$this->attempts++;
        if(str_starts_with($sql,'INSERT INTO ita_license_allocations'))$this->used++;
        if(str_starts_with($sql,'UPDATE ita_license_allocations'))$this->used--;
        return 1;
    }
}
putenv('ITA_LICENSE_KEY='.base64_encode(random_bytes(32)));
$db=new Db06();$admin=actor06();$fields=['software_name'=>'QA','vendor'=>'','license_type'=>'per_device','total_seats'=>'2','start_date'=>'','expire_date'=>'','cost'=>'','status'=>'active','license_key'=>'QA-SECRET'];
$service=new ItaLicenseService($db,$admin);
$result=$service->save($fields);ok06($result['id']===7);
$insert=array_values(array_filter($db->writes,fn($w)=>str_starts_with($w[0],'INSERT INTO ita_licenses')))[0];
ok06(str_starts_with($insert[1][2],'v1:')&&!str_contains($insert[1][2],'QA-SECRET'));
foreach($db->writes as [$sql,$params])if(str_contains($sql,'ita_audit_logs'))ok06(!str_contains(json_encode($params),'QA-SECRET'));
$db->row['license_key_enc']=$insert[1][2];
$dto=(new ItaLicenseObjects($db,$admin))->detail(7,1);ok06(!array_key_exists('license_key_enc',$dto['license']));
ok06($dto['license']['has_key']&&$dto['license']['key_mask']==='••••••••');
$input=['id'=>7,'version'=>$dto['license']['version'],'operation'=>'allocate','target_id'=>'9'];
$service->save($input);ok06($db->used===1);
rejects06(fn()=>$service->save($input),409);
$version=fn()=>ItaLicenseObjectInfo::present($db->row+['used_seats'=>$db->used])['version'];
$db->used=2;$input['version']=$version();rejects06(fn()=>$service->save($input),422);
$service->save($input+['confirm_overage'=>true,'override_reason'=>'QA approved']);ok06($db->used===3);
ok06(count(array_filter($db->writes,fn($w)=>str_contains($w[0],'ita_audit_logs')&&$w[1][2]==='license.overage'))===1);
$db->role='system_manager';$manager=actor06('system_manager');$ms=new ItaLicenseService($db,$manager);
$input['version']=$version();rejects06(fn()=>$ms->save($input+['confirm_overage'=>true,'override_reason'=>'QA']),403);
$db->used=0;$input['version']=$version();$db->department=20;rejects06(fn()=>$ms->save($input),403);
$db->department=10;$ms->save($input);ok06($db->used===1);
rejects06(fn()=>$ms->save($fields),403);
$db->row['license_type']='per_user';$input['version']=$version();rejects06(fn()=>$ms->save($input),403);
rejects06(fn()=>(new ItaLicenseObjects($db,actor06('support_technician')))->listing([],1),403);
$db->role='admin_it';$db->row['license_type']='per_device';$db->failAudit=true;$input['version']=$version();$used=$db->used;
try{$service->save($input);throw new LogicException('Expected audit failure');}catch(RuntimeException $e){ok06($e->getMessage()==='QA audit unavailable');}
ok06($db->rollback&&$db->used===$used);
$db->failAudit=false;
$reveal=new ItaLicenseReveal($db,$admin,['auth_key'=>str_repeat('q',32)]);
rejects06(fn()=>$reveal->reveal(7,'wrong'),401);
$db->attempts=0;$data=$reveal->reveal(7,'qa-password');ok06($data['key']==='QA-SECRET'&&$data['hide_after_seconds']===10);
$db->attempts=0;$db->failAudit=true;
try{$reveal->reveal(7,'qa-password');throw new LogicException('Expected audit failure');}catch(RuntimeException $e){ok06($e->getMessage()==='QA audit unavailable');}
ok06($db->rollback);$db->failAudit=false;$db->attempts=5;rejects06(fn()=>$reveal->reveal(7,'qa-password'),429);
$db->attempts=0;$db->revision=2;rejects06(fn()=>$reveal->reveal(7,'qa-password'),403);
rejects06(fn()=>(new ItaLicenseReveal($db,actor06('system_manager'),['auth_key'=>str_repeat('q',32)]))->reveal(7,'qa-password'),403);
rejects06(fn()=>(new ItaLicenseReveal($db,actor06('support_technician'),['auth_key'=>str_repeat('q',32)]))->reveal(7,'qa-password'),403);
ok06(ItaLicenseReveal::verify(['password_hash'=>password_hash('qa',PASSWORD_DEFAULT)],'qa'));
ok06(!ItaLicenseReveal::verify(['password_hash'=>password_hash('qa',PASSWORD_DEFAULT),'password'=>md5('wrong')],'wrong'));
$db->revision=1;$db->role='system_manager';$objects=new ItaLicenseObjects($db,$manager);$objects->detail(7,1);
ok06(count(array_filter($db->reads,fn($r)=>str_contains($r[0],'department_id IN (?)')&&in_array(10,$r[1],true)))>0);
$alerts=$objects->alerts();ok06($alerts['total']===3&&$alerts['counts']['critical']===2&&count($alerts['alerts'])===1);
ok06(!str_contains(json_encode($alerts),'QA-SECRET'));
$db->role='admin_it';$db->revision=1;$db->used=1;
$release=['id'=>7,'operation'=>'release','allocation_id'=>8,'version'=>$version()];$service->save($release);ok06($db->used===0);
$db->row['expire_date']='2000-01-01';$input['version']=$version();rejects06(fn()=>$service->save($input),422);
$db->row['expire_date']=null;$db->row['status']='inactive';$input['version']=$version();rejects06(fn()=>$service->save($input),422);
$db->row['status']='active';$db->row['start_date']='2099-01-01';$input['version']=$version();rejects06(fn()=>$service->save($input),422);
$_SESSION['ita_csrf']='qa-token';$_SERVER['REQUEST_METHOD']='GET';
$app=new ItaApplication();$reflection=new ReflectionClass($app);$reflection->getProperty('action')->setValue($app,'license-key');
rejects06(fn()=>$reflection->getMethod('api')->invoke($app),405);
$_SERVER['REQUEST_METHOD']='POST';unset($_SERVER['HTTP_X_CSRF_TOKEN']);
rejects06(fn()=>$reflection->getMethod('api')->invoke($app),403);
putenv('ITA_LICENSE_KEY');
echo "$checks scripted service/crypto/scope/audit/reauth checks passed; no DB.\n";
