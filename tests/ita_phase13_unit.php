<?php
/** Pure/query-contract transport doubles; never connects to a database. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function networkCheck(bool $ok,string $label=''): void {global $checks;if(!$ok)throw new RuntimeException('Check '.($checks+1).' '.$label);$checks++;}
function networkActor(string $role='admin_it'): ItaUserObjectInfo {return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>2,'fullname'=>'QA','username'=>'qa'],[10]);}
function networkError(callable $work,int $status): void {try{$work();throw new RuntimeException('Expected '.$status);}catch(ItaHttpError $e){networkCheck($e->status===$status);}}
function networkRow(): array {return ['id'=>7,'source_asset_id'=>2,'target_asset_id'=>3,'link_type'=>'fiber','source_port'=>'A','target_port'=>'B','capacity_label'=>'1G','note'=>'QA','is_deleted'=>0,'updated_at'=>'2026-10-23 00:00:00.000001'];}
function networkInput(): array {return ['source_asset_id'=>'3','target_asset_id'=>'2','source_port'=>'B','target_port'=>'A','link_type'=>'fiber','capacity_label'=>'1G','note'=>'QA'];}
class NetworkDb extends ItaDb
{
    public array $calls=[],$nodes=[],$links=[],$rows=[];
    public int $total=0,$begun=0,$committed=0,$rolled=0;
    public bool $native=true,$enabled=true,$auditFails=false,$software=false,$deleted=false,$staleLock=false;
    public ?array $existing=null,$duplicate=null,$detail=null;
    public string $isolation='REPEATABLE-READ';
    public function __construct(){}
    public function transactionIsolation(): string{return $this->isolation;}
    public function beginTransaction(): void{$this->begun++;}
    public function commit(): void{$this->committed++;}
    public function rollBack(): void{$this->rolled++;}
    private function log(string $sql,string $types,array $params): void {networkCheck(strlen($types)===count($params),'bind count');$this->calls[]=[$sql,$types,$params];}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        $this->log($sql,$types,$params);
        if(str_contains($sql,'FROM ita_user_roles'))return ['role'=>'admin_it','enabled'=>$this->enabled?1:0,'auth_revision'=>2];
        if(str_contains($sql,'FROM dc_users'))return $this->native?['id'=>111]:null;
        if(str_starts_with($sql,'SELECT id,category_id'))return ['id'=>$params[1],'category_id'=>6,'is_deleted'=>$this->deleted?1:0];
        if(str_starts_with($sql,'SELECT is_software'))return ['is_software'=>$this->software?1:0];
        if(str_starts_with($sql,'SELECT l.*,a.name source_name'))return $this->detail;
        if(str_starts_with($sql,'SELECT * FROM ita_network_links')){
            $r=$this->existing;if($r&&$this->staleLock&&str_contains($sql,'FOR UPDATE'))$r['updated_at']='changed';return $r;
        }
        if(str_starts_with($sql,'SELECT id FROM ita_network_links'))return $this->duplicate;
        if(str_starts_with($sql,'SELECT LAST_INSERT_ID'))return ['id'=>8];
        if(str_starts_with($sql,'SELECT COUNT(*)'))return ['n'=>$this->total];
        throw new RuntimeException('Unexpected query '.$sql);
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        $this->log($sql,$types,$params);
        if(str_contains($sql,'LIMIT 201'))return $this->nodes;
        if(str_contains($sql,'LIMIT 401'))return $this->links;
        if(str_contains($sql,'LIMIT 20 OFFSET ?'))return $this->rows;
        throw new RuntimeException('Unexpected list query');
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        $this->log($sql,$types,$params);
        networkCheck(!preg_match('/^(DELETE|CREATE|ALTER|DROP|REPLACE|TRUNCATE)\b/i',$sql),'no DDL/destructive SQL');
        if(str_contains($sql,'ita_audit_logs')&&$this->auditFails)throw new RuntimeException('QA audit failure');
        return 1;
    }
}
$v=ItaNetworkRules::values(networkInput());networkCheck($v['source_asset_id']===2&&$v['target_asset_id']===3&&$v['source_port']==='A'&&$v['target_port']==='B','canonical endpoints and ports');
foreach([['target_asset_id'=>'3'],['link_type'=>'bogus'],['source_asset_id'=>[]],['note'=>str_repeat('x',1001)],['source_port'=>"bad\0"]] as $bad){networkError(fn()=>ItaNetworkRules::values(array_replace(networkInput(),$bad)),422);}
foreach(['system_manager','support_technician'] as $role){
 $db=new NetworkDb();networkError(fn()=>(new ItaNetworkService($db,networkActor($role)))->save(networkInput()),403);networkCheck(!$db->calls&&!$db->begun);
}
$db=new NetworkDb();networkError(fn()=>new ItaNetworkObjects($db,networkActor('support_technician')),403);networkCheck(!$db->calls);
$db=new NetworkDb();$saved=(new ItaNetworkService($db,networkActor()))->save(networkInput());networkCheck($saved===['id'=>8]&&$db->committed===1&&$db->rolled===0);
$locks=array_values(array_filter($db->calls,fn($c)=>str_starts_with($c[0],'SELECT id,category_id')));networkCheck(array_column(array_column($locks,2),1)===[2,3],'sorted locks');
$writes=array_values(array_filter($db->calls,fn($c)=>str_starts_with($c[0],'INSERT')));networkCheck($writes[0][2]===[2,3,'fiber','A','B','1G','QA',1]);
networkCheck(str_contains($writes[1][0],'ita_audit_logs')&&$writes[1][2][2]==='network.save'&&str_contains($writes[1][2][6],'source_asset_id'));
foreach(['native','enabled'] as $field){$db=new NetworkDb();$db->$field=false;networkError(fn()=>(new ItaNetworkService($db,networkActor()))->save(networkInput()),403);networkCheck($db->rolled===1&&$db->committed===0);}
foreach(['software','deleted'] as $field){$db=new NetworkDb();$db->$field=true;networkError(fn()=>(new ItaNetworkService($db,networkActor()))->save(networkInput()),422);networkCheck($db->rolled===1);}
$db=new NetworkDb();$db->duplicate=['id'=>9];networkError(fn()=>(new ItaNetworkService($db,networkActor()))->save(networkInput()),409);networkCheck($db->rolled===1&&!$db->committed);
$db=new NetworkDb();$db->auditFails=true;try{(new ItaNetworkService($db,networkActor()))->save(networkInput());throw new LogicException('expected audit failure');}catch(RuntimeException $e){networkCheck($e->getMessage()==='QA audit failure');}networkCheck($db->rolled===1&&!$db->committed,'audit fail closed transport');
foreach(['stale','lockchange','valid','delete'] as $case){
 $db=new NetworkDb();$db->existing=networkRow();$db->duplicate=['id'=>7];$db->staleLock=$case==='lockchange';
 $input=networkInput()+['id'=>7,'version'=>$case==='stale'?'wrong':ItaNetworkObjectInfo::version(networkRow())];
 if($case==='delete')$input=['id'=>7,'version'=>ItaNetworkObjectInfo::version(networkRow()),'action'=>'delete'];
 if(in_array($case,['stale','lockchange'])){networkError(fn()=>(new ItaNetworkService($db,networkActor()))->save($input),409);networkCheck($db->rolled===1);}
 else{$r=(new ItaNetworkService($db,networkActor()))->save($input);networkCheck($r['id']===7&&$db->committed===1);$update=array_values(array_filter($db->calls,fn($c)=>str_starts_with($c[0],'UPDATE ita_network_links')));networkCheck(count($update)===1);if($case==='delete')networkCheck(str_contains($update[0][0],'SET is_deleted=1'));}
}
foreach([['id'=>0],['id'=>[]],['action'=>'delete'],['action'=>'drop']] as $bad){$db=new NetworkDb();networkError(fn()=>(new ItaNetworkService($db,networkActor()))->save(array_replace(networkInput(),$bad)),422);networkCheck(!$db->begun);}
foreach(['READ-COMMITTED','READ-UNCOMMITTED'] as $isolation){$db=new NetworkDb();$db->isolation=$isolation;networkError(fn()=>(new ItaNetworkService($db,networkActor()))->save(networkInput()),503);networkCheck(!$db->begun);}
foreach(['admin_it','system_manager'] as $role){
 $db=new NetworkDb();$db->nodes=[['id'=>2],['id'=>3]];$db->total=21;$db->rows=[networkRow()];
 $data=(new ItaNetworkService($db,networkActor($role)))->read(fn()=>(new ItaNetworkObjects($db,networkActor($role)))->listing(['q'=>'%_!'],99));
 networkCheck($data['page']===2&&$data['total']===21&&$data['mock']===false&&$data['can_manage']===($role==='admin_it'));
 networkCheck($db->calls[0][2]===($role==='admin_it'?[1,'%!%!_!!%','%!%!_!!%']:[1,10,'%!%!_!!%','%!%!_!!%']),'literal filter');
 $count=$db->calls[1];networkCheck(str_contains($count[0],'a.is_deleted=0')&&str_contains($count[0],'b.is_deleted=0')&&str_contains($count[0],'ca.is_software=0 AND cb.is_software=0'));
 if($role==='system_manager')networkCheck(str_contains($count[0],'a.department_id IN (?)')&&str_contains($count[0],'b.department_id IN (?)')&&$count[2]===[1,1,10,1,10,2,3,2,3]);
 networkCheck($db->calls[3][2][count($db->calls[3][2])-1]===20&&str_contains($db->calls[3][0],'ORDER BY l.id DESC LIMIT 20 OFFSET ?'));
 $networkExplainPlans[$role]=$db->calls;
}
$db=new NetworkDb();networkError(fn()=>(new ItaNetworkObjects($db,networkActor('system_manager')))->listing(['department_id'=>'20'],1),403);networkCheck(!$db->calls);
foreach(['nodes','links','total'] as $overflow){$db=new NetworkDb();if($overflow==='nodes')$db->nodes=array_fill(0,201,['id'=>2]);if($overflow==='links')$db->links=array_fill(0,401,[]);if($overflow==='total')$db->total=401;networkError(fn()=>(new ItaNetworkObjects($db,networkActor()))->listing([],1),422);}
foreach([10,20] as $department){$db=new NetworkDb();$db->detail=networkRow()+['source_department'=>10,'target_department'=>$department,'source_deleted'=>0,'target_deleted'=>0,'source_software'=>0,'target_software'=>0];$dao=new ItaNetworkObjects($db,networkActor('system_manager'));if($department===20)networkError(fn()=>$dao->detail(7),403);else networkCheck($dao->detail(7)['id']===7);}
require dirname(__DIR__).'/includes/ita_mode.inc.php';
networkCheck(itaRequestDecision('/admin.php',['op'=>'ita','act'=>'network-save'],'POST',['enabled'=>true,'legacy_enabled'=>false])==='shell');
$_SESSION=['ita_csrf'=>'qa'];networkError(fn()=>ItaSession::requireCsrf('wrong'),403);
// Invoke actual dispatcher method with fake transport: no run()/configuration/connect().
$db=new NetworkDb();$app=new ItaApplication();
foreach(['db'=>$db,'user'=>networkActor(),'action'=>'network-save'] as $key=>$value){$property=new ReflectionProperty($app,$key);$property->setValue($app,$value);}
$method=new ReflectionMethod($app,'networkApi');
$_SERVER['REQUEST_METHOD']='GET';networkError(fn()=>$method->invoke($app),405);networkCheck(!$db->calls);
$_SERVER['REQUEST_METHOD']='POST';$_SERVER['HTTP_X_CSRF_TOKEN']='wrong';networkError(fn()=>$method->invoke($app),403);networkCheck(!$db->calls&&!$db->begun);
foreach(['network','network-detail'] as $action){(new ReflectionProperty($app,'action'))->setValue($app,$action);networkError(fn()=>$method->invoke($app),405);networkCheck(!$db->calls);}
$_SERVER['REQUEST_METHOD']='GET';$_GET=['page'=>'1'];(new ReflectionProperty($app,'action'))->setValue($app,'network');
ob_start();$method->invoke($app);$json=json_decode(ob_get_clean(),true,512,JSON_THROW_ON_ERROR);networkCheck($json['success']===true&&$json['data']['mock']===false&&$db->committed===1,'actual GET dispatch envelope');
echo "PASS: $checks pure/query-contract checks; no DB, persistence or real concurrency proof.\n";
