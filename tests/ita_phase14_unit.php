<?php
/** Capability mode with fake transport only. No config/bootstrap/database connections. */
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
define('ITA_CAPABILITIES_ENABLED',true);
require __DIR__.'/../classes/security/itahttperror.class.php';
require __DIR__.'/../classes/security/itainput.class.php';
require __DIR__.'/../classes/dao/itauserobjectinfo.class.php';
class ItaDb
{
    public array $users=[],$scopes=[],$permissions=[],$writes=[],$audits=[];
    public int $begins=0,$commits=0,$rollbacks=0;
    public bool $auditFail=false;
    public array $assetDepartments=[21=>10,22=>10];
    private array $snapshot=[];
    public function __construct()
    {
        $this->users=[1=>['id'=>1,'username'=>'MOCK-owner','fullname'=>'MOCK owner','status'=>1,'role'=>'admin_it','enabled'=>1,'auth_revision'=>5],111=>['id'=>111,'username'=>'MOCK-C','fullname'=>'MOCK C','status'=>1,'role'=>'support_technician','enabled'=>1,'auth_revision'=>3]];
        $this->scopes=[1=>[],111=>[10]];$this->permissions=[1=>ItaPermissions::KEYS,111=>['asset.read']];
    }
    private function bind(string $sql,string $types,array $params): void { if (substr_count($sql,'?')!==count($params) || strlen($types)!==count($params)) { throw new RuntimeException('Bind contract mismatch'); } }
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        $this->bind($sql,$types,$params);$id=(int)($params[1]??1);
        if (str_starts_with($sql,'SELECT role,enabled,auth_revision')) { return $this->users[$id]??null; }
        if (str_starts_with($sql,'SELECT id FROM dc_users')) { return ($this->users[$id]['status']??0)===1?['id'=>$id]:null; }
        if (str_starts_with($sql,'SELECT id,category_id,is_deleted,department_id')) { return ['id'=>$id,'category_id'=>2,'is_deleted'=>0,'department_id'=>$this->assetDepartments[$id]]; }
        if (str_starts_with($sql,'SELECT is_software')) { return ['is_software'=>0]; }
        if (str_starts_with($sql,'SELECT id FROM ita_network_links')) { return null; }
        if ($sql==='SELECT LAST_INSERT_ID() id') { return ['id'=>91]; }
        if (str_contains($sql,'COUNT(*) n FROM ita_departments')) { return ['n'=>count(array_filter(array_slice($params,1),fn($id)=>$id===10))]; }
        if (str_starts_with($sql,'SELECT auth_revision')) { return isset($this->users[$id])?['auth_revision'=>$this->users[$id]['auth_revision']]:null; }
        if (str_contains($sql,'JOIN ita_user_roles r')) { $u=$this->users[$id]??null;return $u && $u['status']===1 && $u['enabled']===1?$u:null; }
        if (str_contains($sql,'LEFT JOIN ita_user_roles')) { return $this->users[$id]??null; }
        throw new RuntimeException('Unexpected fake fetchOne: '.$sql);
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        $this->bind($sql,$types,$params);$id=(int)($params[1]??1);
        if (str_starts_with($sql,'SELECT permission_key')) { $keys=$this->permissions[$id]??[];sort($keys);return array_map(fn($key)=>['permission_key'=>$key],$keys); }
        if (str_starts_with($sql,'SELECT s.department_id') || str_starts_with($sql,'SELECT department_id')) { return array_map(fn($id)=>['department_id'=>$id],$this->scopes[$id]??[]); }
        if (str_starts_with($sql,'SELECT user_id,enabled,auth_revision')) { return array_map(fn($u)=>['user_id'=>$u['id']]+$u,array_values($this->users)); }
        if (str_starts_with($sql,'SELECT p.user_id')) { return array_values(array_map(fn($u)=>['user_id'=>$u['id']],array_filter($this->users,fn($u)=>$u['enabled']===1&&$u['status']===1&&in_array('access.manage',$this->permissions[$u['id']]??[],true)))); }
        throw new RuntimeException('Unexpected fake fetchAll: '.$sql);
    }
    public function execute(string $sql,string $types='',array $params=[]): void
    {
        $this->bind($sql,$types,$params);$this->writes[]=$sql;
        if (str_starts_with($sql,'INSERT INTO ita_network_links')) { return; }
        if (str_starts_with($sql,'INSERT INTO ita_user_roles')) { $this->users[$params[1]]['enabled']=$params[3];$this->users[$params[1]]['auth_revision']++;return; }
        if (str_starts_with($sql,'UPDATE ita_user_scopes')) { $this->scopes[$params[1]]=[];return; }
        if (str_starts_with($sql,'INSERT INTO ita_user_scopes')) { $this->scopes[$params[1]][]=$params[2];return; }
        if (str_starts_with($sql,'UPDATE ita_user_permissions')) { $this->permissions[$params[2]]=[];return; }
        if (str_starts_with($sql,'INSERT INTO ita_user_permissions')) { $this->permissions[$params[1]][]=$params[2];return; }
        if (str_starts_with($sql,'INSERT INTO ita_audit_logs')) { if ($this->auditFail)throw new RuntimeException('MOCK audit failure');$this->audits[]=$params;return; }
        throw new RuntimeException('Unexpected fake write');
    }
    public function beginTransaction(): void { $this->begins++;$this->snapshot=[$this->users,$this->scopes,$this->permissions,$this->audits]; }
    public function commit(): void { $this->commits++; }
    public function transactionIsolation(): string { return 'REPEATABLE-READ'; }
    public function rollBack(): void { $this->rollbacks++;[$this->users,$this->scopes,$this->permissions,$this->audits]=$this->snapshot; }
}
foreach (['dao/itauserobjects','services/itaauditservice','services/itaaccessservice','services/itaassetservice','services/itaassignmentservice','services/italicenseservice','services/itaticketservice','services/itanetworkrules','services/itanetworkservice'] as $file) { require __DIR__.'/../classes/'.$file.'.class.php'; }
$checks=0;
function check14(bool $ok,string $label): void { global $checks;if(!$ok)throw new RuntimeException($label);$checks++; }
function denied14(callable $work,int $status,string $label): void { try{$work();}catch(ItaHttpError $error){check14($error->status===$status,$label);return;}throw new RuntimeException('Expected denial: '.$label); }
function actor14(array $permissions,int $id=111,string $role='support_technician'): ItaUserObjectInfo { return new ItaUserObjectInfo(['id'=>$id,'store_id'=>1,'username'=>'MOCK','fullname'=>'MOCK actor','role'=>$role,'auth_revision'=>$id===1?5:3,'permissions'=>$permissions],$id===1?[]:[10]); }
function payload14(ItaDb $db,ItaUserObjectInfo $owner,int $id=111): array { $target=(new ItaUserObjects($db))->access($owner,$id);return ['user_id'=>$id,'enabled'=>true,'department_ids'=>$id===1?[]:[10],'permissions'=>$target['permissions'],'version'=>$target['version']]; }
$c=actor14(['asset.read','asset.create','report.read']);
check14($c->can('asset.create')&&$c->can('report.read')&&!$c->can('asset.update'),'C functions1/2/6 without fourth role');
$fakeAdmin=actor14([],111,'admin_it');check14(!$fakeAdmin->can('asset.read')&&!$fakeAdmin->hasGlobalScope()&&!$fakeAdmin->isAdmin(),'Legacy Admin label cannot bypass empty enabled-mode permissions');
denied14(fn()=>$c->requireCapability('asset.update'),403,'Missing function denied');denied14(fn()=>$c->requireDepartment(20),403,'Scope B denied');$c->requireDepartment(10);check14(true,'Scope A allowed');
denied14(fn()=>actor14(['asset.create']),422,'Missing read dependency rejected');denied14(fn()=>ItaPermissions::validate(['forged.root']),422,'Forged permission rejected');
foreach (['asset','asset-export','audit','users','access','lifecycle','network','license','ticket-statistics'] as $route) { denied14(fn()=>ItaPermissions::read($fakeAdmin,$route,[]),403,'GET '.$route.' denied without grant'); }
ItaPermissions::read($c,'asset',[]);ItaPermissions::read($c,'report-preview',['report'=>'inventory']);check14(true,'C authorized reads pass');
$db=new ItaDb();foreach (['asset'=>new ItaAssetService($db,$c),'assignment'=>new ItaAssignmentService($db,$c),'license'=>new ItaLicenseService($db,$c),'ticket'=>new ItaTicketService($db,$c)] as $key=>$service) {
    $op=['asset'=>'update','assignment'=>'assign','license'=>'save','ticket'=>'create'][$key];denied14(fn()=>$service->save(['operation'=>$op]),403,'Direct '.$key.' writer denies before DB');
}
check14($db->begins===0&&!$db->writes,'Unauthorized service writes never start transaction');
$db=new ItaDb();$owner=actor14(ItaPermissions::KEYS,1,'admin_it');$input=payload14($db,$owner);$input['permissions']=['asset.read','asset.create','report.read'];$input['role']='admin_it';
(new ItaAccessService($db,$owner))->update($input);
check14($db->commits===1&&$db->rollbacks===0,'Capability grant transaction committed');check14(count($db->permissions[111])===3&&in_array('report.read',$db->permissions[111],true),'C exact grants persisted in fake transport');check14($db->users[111]['role']==='support_technician','Forged role ignored; no new role');check14($db->users[111]['auth_revision']===4,'Grant changes invalidate old revision');check14(count($db->audits)===1&&str_contains($db->audits[0][6],'permissions'),'Grant audit includes explicit permissions');check14(!array_filter($db->writes,fn($q)=>preg_match('/(?:UPDATE|INSERT INTO)\s+dc_/i',$q)),'No native writes');
denied14(fn()=>(new ItaAccessService($db,$owner))->update($input),409,'Stale access version denied');
$db=new ItaDb();$input=payload14($db,$owner,1);$input['enabled']=false;denied14(fn()=>(new ItaAccessService($db,$owner))->update($input),409,'Last capability manager protected');check14(!$db->writes&&$db->rollbacks===1,'Last manager denial no writes');
$db=new ItaDb();$input=payload14($db,$owner);$input['enabled']=false;
(new ItaAccessService($db,$owner))->update($input);
check14($db->users[111]['enabled']===0&&$db->users[111]['auth_revision']===4,'ITA revoke disables mapping and invalidates old revision');
check14($db->users[111]['status']===1&&!array_filter($db->writes,fn($q)=>preg_match('/(?:UPDATE|INSERT INTO|DELETE FROM)\s+dc_/i',$q)),'ITA revoke never changes native account');
check14($db->scopes[111]===[10]&&$db->permissions[111]===['asset.read'],'ITA revoke preserves selected scope and permission for restoration');
check14(count($db->audits)===1&&$db->audits[0][2]==='user.access_update','ITA revoke recorded in audit');
$db=new ItaDb();$input=payload14($db,$owner);$input['permissions']=['asset.create'];denied14(fn()=>(new ItaAccessService($db,$owner))->update($input),422,'Writer rejects incomplete dependencies');check14($db->begins===0,'Dependency failure before transaction');
$db=new ItaDb();$input=payload14($db,$owner);$input['department_ids']=[20];denied14(fn()=>(new ItaAccessService($db,$owner))->update($input),422,'Foreign/inactive department rejected');
$db=new ItaDb();$limited=actor14(['access.read','access.manage','scope.store'],1,'admin_it');$input=payload14($db,$limited);$input['permissions']=['report.read'];denied14(fn()=>(new ItaAccessService($db,$limited))->update($input),403,'Cannot delegate function not owned');
$db=new ItaDb();$input=payload14($db,$owner);$input['permissions']=['asset.read','asset.create','report.read'];$before=[$db->users,$db->scopes,$db->permissions];$db->auditFail=true;
try{(new ItaAccessService($db,$owner))->update($input);throw new RuntimeException('Expected audit failure');}catch(RuntimeException $e){check14($e->getMessage()==='MOCK audit failure','Audit failure propagated');}
check14($db->rollbacks===1&&[$db->users,$db->scopes,$db->permissions]===$before,'Audit failure invokes rollback and fake state restored; not InnoDB proof');
$network=actor14(['network.read','network.manage']);$link=['source_asset_id'=>21,'target_asset_id'=>22,'link_type'=>'ethernet'];
$db=new ItaDb();$result=(new ItaNetworkService($db,$network))->save($link);
check14($result['id']===91&&$db->commits===1&&count($db->audits)===1,'Non-Admin role with network.manage saves scoped endpoints');
$db=new ItaDb();$db->assetDepartments[22]=20;
denied14(fn()=>(new ItaNetworkService($db,$network))->save($link),403,'Network writer rejects second endpoint outside granted department');
check14(!$db->writes&&$db->rollbacks===1,'Out-of-scope link denied before link or audit write');
$db=new ItaDb();$db->users[111]['auth_revision']++;
denied14(fn()=>(new ItaNetworkService($db,$network))->save($link),403,'Network writer rejects revoked revision');
$routes=['asset','users','access','audit','assignments','assignment-recipients','assignment-pdf','licenses','license','tickets','ticket','ticket-technicians','lifecycle','lifecycle-detail','forecast','forecast-config','forecast-plans','forecast-plan','dashboard','dashboard-charts','alerts','network','network-detail','import-template','import-preview','import-errors','asset-export','ticket-statistics','report-preview','categories'];
foreach ($routes as $route) {
    $query=$route==='categories'?['paged'=>'1']:[];
    denied14(fn()=>ItaPermissions::read($fakeAdmin,$route,$query),403,'No legacy Admin bypass at GET '.$route);
    ItaPermissions::read($owner,$route,$query);check14(true,'Explicit full grant GET '.$route);
}
foreach (['asset'=>['create','update','status','note','delete'],'assignment'=>['assign','return','acknowledge'],'license'=>['save','allocate','release'],'ticket'=>['create','assign','transition','cost','ready']] as $kind=>$operations) {
    $db=new ItaDb();$class=['asset'=>ItaAssetService::class,'assignment'=>ItaAssignmentService::class,'license'=>ItaLicenseService::class,'ticket'=>ItaTicketService::class][$kind];
    foreach ($operations as $operation) { denied14(fn()=>(new $class($db,$fakeAdmin))->save(['operation'=>$operation]),403,'No role bypass at POST '.$kind.'.'.$operation); }
    check14($db->begins===0&&!$db->writes,'No denied '.$kind.' mutation reaches transaction');
}
echo "$checks capability/service/route checks passed; fake transport only, no DB/host.\n";
