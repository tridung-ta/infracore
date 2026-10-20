<?php
/** Pure rules and scripted persistence only: no configuration, MySQL or host access. */
if (PHP_SAPI!=='cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks = 0;
function expect05(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
function rejects05(callable $callback, int $status): void
{
    try { $callback(); }
    catch (ItaHttpError $error) { expect05($error->status===$status,'Expected '.$status.', got '.$error->status); return; }
    throw new RuntimeException('Expected rejection '.$status);
}
function actor05(string $role = 'admin_it', array $scope = [10]): ItaUserObjectInfo
{
    return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'fullname'=>'QA','username'=>'qa'],$scope);
}
class Adapter05 extends ItaDb
{
    public array $asset = ['id'=>7,'store_id'=>1,'asset_code'=>'QA-7','name'=>'QA','status'=>'in_use','category_id'=>2,'department_id'=>10,'current_user_id'=>null,'is_deleted'=>0,'next_maintenance_due'=>null];
    public ?array $active = null;
    public array $reads = [];
    public array $writes = [];
    public array $history = [];
    public bool $committed = false;
    public bool $rolledBack = false;
    public bool $userActive = true;
    public bool $departmentActive = true;
    public bool $roleEnabled = true;
    public bool $failAudit = false;
    public bool $duplicate = false;
    public bool $pendingTicket = false;
    public string $role = 'admin_it';
    private array $snapshot = [];
    public function __construct() {}
    public function beginTransaction(): void { $this->snapshot=[$this->asset,$this->active]; }
    public function commit(): void { $this->committed=true; }
    public function rollBack(): void { [$this->asset,$this->active]=$this->snapshot; $this->rolledBack=true; }
    public function fetchOne(string $sql, string $types = '', array $params = []): ?array
    {
        expect05(strlen($types)===count($params),'Prepared read parameters match');
        $this->reads[]=[$sql,$params];
        if (str_contains($sql,'ita_tickets')) return $this->pendingTicket ? ['id'=>9] : null;
        if (str_contains($sql,'ita_user_roles')) return ['role'=>$this->role,'enabled'=>(int) $this->roleEnabled,'auth_revision'=>1];
        if (str_contains($sql,'LAST_INSERT_ID')) return ['id'=>9];
        if (str_contains($sql,'COUNT(*)')) return ['n'=>str_contains($sql,'next_maintenance_due')?1:count($this->history)];
        if (str_contains($sql,'ita_asset_categories')) return ['maintenance_cycle_months'=>6];
        if (str_contains($sql,'ita_assets')) return $params[0]===1 && $params[1]===7 && !$this->asset['is_deleted'] ? $this->asset : null;
        if (str_contains($sql,'ita_assignments')) return str_contains($sql,'returned_at IS NULL') && $this->active && $this->active['returned_at']!==null ? null : $this->active;
        if (str_contains($sql,'dc_users')) return $this->userActive ? ['id'=>$params[1]] : null;
        if (str_contains($sql,'ita_departments')) return $this->departmentActive ? ['id'=>$params[1]] : null;
        throw new RuntimeException('Unexpected read '.$sql);
    }
    public function fetchAll(string $sql, string $types = '', array $params = []): array
    {
        expect05(strlen($types)===count($params),'Prepared list parameters match');
        $this->reads[]=[$sql,$params];
        if (str_contains($sql,'ita_assignments')) return array_slice($this->history,end($params),20);
        if (str_contains($sql,'dc_users')) return [['id'=>222,'username'=>'qa','fullname'=>'QA']];
        if (str_contains($sql,'GROUP BY a.status')) return [['status'=>'in_use','n'=>1]];
        if (str_contains($sql,'next_maintenance_due')) return [$this->asset];
        if (str_contains($sql,'FROM ita_assets a')) return [];
        throw new RuntimeException('Unexpected list '.$sql);
    }
    public function execute(string $sql, string $types = '', array $params = []): int
    {
        expect05(strlen($types)===count($params),'Prepared write parameters match');
        $this->writes[]=[$sql,$params];
        if ($this->duplicate && str_contains($sql,'INSERT INTO ita_assignments')) throw new mysqli_sql_exception('QA duplicate',1062);
        if ($this->failAudit && str_contains($sql,'ita_audit_logs')) throw new RuntimeException('QA audit failure');
        if (str_contains($sql,'INSERT INTO ita_assignments')) $this->active=['id'=>9,'asset_id'=>7,'to_user_id'=>$params[2],'to_department_id'=>$params[3],
            'assigned_by'=>$params[4],'due_return'=>$params[5],'handover_no'=>$params[6],'note'=>$params[7],'returned_at'=>null,'acknowledged_by'=>null,'acknowledged_at'=>null,'handover_file'=>'private/file'];
        if (str_contains($sql,'SET current_user_id=?')) { $this->asset['current_user_id']=$params[0];$this->asset['department_id']=$params[1]; }
        if (str_contains($sql,'SET current_user_id=NULL')) $this->asset['current_user_id']=null;
        if (str_contains($sql,'SET returned_at=')) $this->active['returned_at']='2026-10-19 00:00:00';
        if (str_contains($sql,'SET acknowledged_by=')) { $this->active['acknowledged_by']=$params[0];$this->active['acknowledged_at']='2026-10-19 00:00:00'; }
        return 1;
    }
}
function input05(Adapter05 $db, string $operation = 'assign'): array
{
    return ['operation'=>$operation,'asset_id'=>7,'asset_version'=>ItaAssetService::version($db->asset),
        'target_type'=>'user','target_id'=>'222','due_return'=>'','note'=>'QA',
        'assignment_id'=>$db->active['id'] ?? 9,'assignment_version'=>ItaAssetService::version($db->active ?? [])];
}
function assigned05(string $role = 'admin_it'): Adapter05
{
    $db=new Adapter05();$db->role=$role;
    $db->asset['current_user_id']=111;
    $db->active=['id'=>9,'asset_id'=>7,'to_user_id'=>111,'to_department_id'=>null,'returned_at'=>null,'acknowledged_at'=>null,'acknowledged_by'=>null,'handover_file'=>'private/file'];
    return $db;
}
$today=ItaAssignmentRules::today();
expect05(ItaAssignmentRules::dueDate('')===null,'Optional due return');
expect05(ItaAssignmentRules::dueDate($today)===$today,'Same-day due return accepted');
rejects05(fn()=>ItaAssignmentRules::dueDate('2000-01-01'),422);
rejects05(fn()=>ItaAssignmentRules::dueDate('2026-02-30'),422);
rejects05(fn()=>ItaAssignmentRules::dueDate([]),422);
expect05(ItaAssignmentRules::overdue(['status'=>'in_use','next_maintenance_due'=>'2000-01-01']),'Past maintenance is overdue');
expect05(!ItaAssignmentRules::overdue(['status'=>'in_use','next_maintenance_due'=>$today]),'Due today is not overdue');
expect05(!ItaAssignmentRules::overdue(['status'=>'retired','next_maintenance_due'=>'2000-01-01']),'Retired excluded from maintenance alerts');
foreach (['retired','broken','maintenance'] as $status) {
    $db=new Adapter05();$db->asset['status']=$status;
    rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db)),409);
    expect05(!$db->writes && $db->rolledBack,'Unavailable status writes nothing');
}
$db=new Adapter05();$db->role='support_technician';
rejects05(fn()=>(new ItaAssignmentService($db,actor05('support_technician')))->save(input05($db)),403);
$db=new Adapter05();$db->role='system_manager';
rejects05(fn()=>(new ItaAssignmentService($db,actor05('system_manager',[20])))->save(input05($db)),403);
expect05(!$db->writes,'Source scope checked before writes');
rejects05(fn()=>(new ItaAssignmentService($db,actor05('system_manager')))->save(array_replace(input05($db),['target_type'=>'department','target_id'=>'20'])),403);
$db=new Adapter05();
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(array_replace(input05($db),['asset_version'=>'old'])),409);
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(array_replace(input05($db),['target_type'=>'invalid'])),422);
$db->userActive=false;
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db)),422);
$db->userActive=true;$db->departmentActive=false;
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db)),422);
$db->departmentActive=true;$db->roleEnabled=false;
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db)),403);
$db=new Adapter05();$db->asset['current_user_id']=333;
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db)),409);
$db=assigned05();rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db)),409);
$db=new Adapter05();$db->duplicate=true;
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db)),409);
expect05($db->rolledBack && $db->active===null,'Duplicate invokes rollback');
$db=new Adapter05();$db->failAudit=true;
try{(new ItaAssignmentService($db,actor05()))->save(input05($db));throw new LogicException('Expected audit failure');}
catch(RuntimeException $error){expect05($db->rolledBack && !$db->committed && $db->active===null,'Audit failure rolls back scripted state');}
$db=new Adapter05();$result=(new ItaAssignmentService($db,actor05()))->save(input05($db)+['store_id'=>2,'assigned_by'=>999]);
expect05($db->committed && $result['id']===9 && $db->active['assigned_by']===111 && $db->asset['current_user_id']===222,'Assign and audit commit, forged actor/store ignored');
expect05(str_starts_with($db->active['handover_no'],'ITA-'),'Server creates receipt number');
$db=new Adapter05();$db->role='system_manager';
(new ItaAssignmentService($db,actor05('system_manager',[10,20])))->save(array_replace(input05($db),['target_type'=>'department','target_id'=>'20']));
expect05($db->asset['department_id']===20 && $db->active['to_user_id']===null && $db->active['to_department_id']===20,'Department assignment moves scoped asset, exactly one target');
$db=assigned05('support_technician');
rejects05(fn()=>(new ItaAssignmentService($db,actor05('support_technician')))->save(array_replace(input05($db,'return'),['note'=>''])),422);
rejects05(fn()=>(new ItaAssignmentService($db,actor05('support_technician')))->save(array_replace(input05($db,'return'),['assignment_version'=>'old'])),409);
rejects05(fn()=>(new ItaAssignmentService($db,actor05('support_technician')))->save(array_replace(input05($db,'return'),['assignment_id'=>88])),409);
(new ItaAssignmentService($db,actor05('support_technician')))->save(input05($db,'return'));
expect05($db->committed && $db->asset['current_user_id']===null && $db->active['returned_at']!==null,'Support can receive return in scope');
$audit=array_filter($db->writes,fn($write)=>str_contains($write[0],'ita_audit_logs'));
expect05(str_contains(json_encode($audit),'return_note'),'Return condition recorded in assignment audit');
rejects05(fn()=>(new ItaAssignmentService($db,actor05('support_technician')))->save(input05($db,'return')),409);
$db=assigned05();$db->active['to_user_id']=222;
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db,'acknowledge')),403);
expect05(!$db->writes,'Admin cannot impersonate recipient');
$db=assigned05();$db->active['to_user_id']=null;$db->active['to_department_id']=10;
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db,'acknowledge')),403);
$db=assigned05();(new ItaAssignmentService($db,actor05()))->save(input05($db,'acknowledge')+['acknowledged_by'=>222]);
expect05($db->active['acknowledged_by']===111 && $db->committed,'Only actual actor acknowledged');
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db,'acknowledge')),409);
$db=assigned05();$db->history=array_fill(0,25,['id'=>9,'handover_no'=>'QA']);
$detail=(new ItaAssignmentObjects($db,actor05()))->detail(7,2);
expect05(count($detail['history'])===5 && $detail['page']===2 && $detail['total']===25,'History is paginated');
expect05(!isset($detail['active']['handover_file']) && isset($detail['active']['version']),'Private receipt path omitted; revision returned');
rejects05(fn()=>(new ItaAssignmentObjects($db,actor05('system_manager',[20])))->detail(7,1),403);
rejects05(fn()=>(new ItaAssignmentObjects($db,actor05('support_technician')))->recipients('',1),403);
(new ItaAssignmentObjects($db,actor05()))->recipients('QA%',1);
$last=$db->reads[array_key_last($db->reads)];
expect05(in_array('%QA=%%',$last[1],true) && str_contains($last[0],'LIMIT 20 OFFSET ?'),'Recipient search bounded and escaped');
$db=new Adapter05();$db->asset['next_maintenance_due']='2020-01-01';
$summary=(new ItaAssetObjects($db,actor05('support_technician')))->summary();
expect05($summary['alerts_total']===1 && str_starts_with($summary['alerts'][0]['title'],'Quá hạn bảo trì:'),'Maintenance contributes to scoped notification total');
$dueReads=array_filter($db->reads,fn($read)=>str_contains($read[0],ItaMaintenanceSchedule::SQL.'<?'));
expect05(count($dueReads)===2 && count(array_filter($dueReads,fn($read)=>str_contains($read[0],'a.department_id IN (?)') && str_contains($read[0],"a.status<>'retired'") && $read[1]===[1,10,ItaAssignmentRules::today()]))===2,'Maintenance queries preserve store/department/retired/timezone predicates');
$_GET=[];$_SESSION=['ita_csrf'=>'qa-token'];$_SERVER['REQUEST_METHOD']='POST';
$application=new ItaApplication();$reflection=new ReflectionClass($application);
foreach (['action'=>'assignment-save','db'=>$db,'user'=>actor05()] as $name=>$value) $reflection->getProperty($name)->setValue($application,$value);
rejects05(fn()=>$reflection->getMethod('api')->invoke($application),403);
$_SERVER['HTTP_X_CSRF_TOKEN']='wrong';
rejects05(fn()=>$reflection->getMethod('api')->invoke($application),403);
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(array_replace(input05($db),['asset_id'=>[]])),422);
$db=new Adapter05();$db->pendingTicket=true;
rejects05(fn()=>(new ItaAssignmentService($db,actor05()))->save(input05($db)),409);
expect05(!$db->writes,'Pending ticket blocks new assignment despite in_use asset');
$db=assigned05('support_technician');$db->pendingTicket=true;
(new ItaAssignmentService($db,actor05('support_technician')))->save(input05($db,'return'));
expect05($db->active['returned_at']!==null,'Return remains allowed while pending');
echo "$checks pure/scripted checks passed; no database created or connected.\n";
