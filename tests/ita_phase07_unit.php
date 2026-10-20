<?php
/** Scripted DB only. No private config, connection, database creation or SQL execution. */
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function ok07(bool $ok,string $label=''): void { global $checks;if(!$ok)throw new RuntimeException('Check '.($checks+1).' '.$label);$checks++; }
function rejects07(callable $fn,int $status): void { try{$fn();}catch(ItaHttpError $e){ok07($e->status===$status,'expected '.$status.' got '.$e->status);return;}throw new RuntimeException('Expected '.$status); }
function actor07(string $role='admin_it',array $scope=[10]): ItaUserObjectInfo { return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'username'=>'qa','fullname'=>'QA'],$scope); }
class Db07 extends ItaDb
{
 public array $asset=['id'=>7,'store_id'=>1,'asset_code'=>'QA-7','name'=>'QA','category_id'=>2,'department_id'=>10,'status'=>'in_use','current_user_id'=>null,'is_deleted'=>0,'last_maintenance_at'=>null,'next_maintenance_due'=>null,'purchase_date'=>'2026-01-31'];
 public ?array $ticket=null;
 public array $writes=[],$reads=[],$events=[];
 public string $role='admin_it';
 public bool $otherPending=false,$failAudit=false,$assigned=false,$rollback=false;
 public int $technicianDepartment=10;
 private array $snapshot=[];
 public function __construct() {}
 public function beginTransaction(): void { $this->snapshot=[$this->asset,$this->ticket,$this->writes,$this->events]; }
 public function commit(): void {}
 public function rollBack(): void { [$this->asset,$this->ticket,$this->writes,$this->events]=$this->snapshot;$this->rollback=true; }
 public function fetchOne(string $sql,string $types='',array $params=[]): ?array {
  ok07(strlen($types)===count($params),'prepared read');$this->reads[]=[$sql,$params];
  if(str_contains($sql,'JOIN ita_user_roles'))return ['id'=>222,'username'=>'qa-tech','fullname'=>'QA Tech','role'=>'support_technician','auth_revision'=>1];
  if(str_contains($sql,'ita_user_roles'))return ['role'=>$this->role,'enabled'=>1,'auth_revision'=>1];
  if(str_contains($sql,'COUNT(*)'))return ['n'=>1];
  if(str_contains($sql,'LAST_INSERT_ID'))return ['id'=>9];
  if(str_contains($sql,'ita_asset_categories'))return ['maintenance_cycle_months'=>6,'status'=>'active','id'=>2];
  if(str_contains($sql,'ita_tickets')) {
   if(str_contains($sql,"status='pending'"))return $this->otherPending?['id'=>99]:((!str_contains($sql,'id<>?')&&($this->ticket['status']??'')==='pending')?['id'=>9]:null);
   return $params[0]===1&&($params[1]===9||$params[1]===7)?$this->ticket:null;
  }
  if(str_contains($sql,'ita_assignments'))return $this->assigned?['id'=>8]:null;
  if(str_contains($sql,'ita_assets'))return $params[0]===1&&$params[1]===7&&!$this->asset['is_deleted']?$this->asset:null;
  throw new RuntimeException('Unexpected read '.$sql);
 }
 public function fetchAll(string $sql,string $types='',array $params=[]): array { ok07(strlen($types)===count($params),'prepared list');$this->reads[]=[$sql,$params];return str_contains($sql,'ita_user_scopes')?[['department_id'=>$this->technicianDepartment]]:[]; }
 public function execute(string $sql,string $types='',array $p=[]): int {
  ok07(strlen($types)===count($p),'prepared write');if($this->failAudit&&str_contains($sql,'ita_audit_logs'))throw new RuntimeException('QA audit failure');$this->writes[]=[$sql,$p];
  if(str_starts_with($sql,'INSERT INTO ita_tickets'))$this->ticket=['id'=>9,'store_id'=>1,'ticket_no'=>$p[1],'asset_id'=>7,'type'=>$p[3],'reported_by'=>111,'technician_id'=>null,'issue'=>$p[5],'severity'=>$p[6],'status'=>'pending','opened_at'=>gmdate('Y-m-d H:i:s'),'handled_date'=>null,'resolved_at'=>null,'cost'=>'0.00','resolution_note'=>null,'updated_at'=>gmdate('Y-m-d H:i:s')];
  if(str_starts_with($sql,'INSERT INTO ita_ticket_events'))$this->events[]=$p;
  if(str_starts_with($sql,'UPDATE ita_assets SET status='))$this->asset['status']=$p[0];
  if(str_starts_with($sql,'UPDATE ita_assets SET last_maintenance')){$this->asset['last_maintenance_at']=$p[0];$this->asset['next_maintenance_due']=$p[1];}
  if(str_starts_with($sql,'UPDATE ita_tickets SET status=?'))$this->ticket=array_replace($this->ticket,['status'=>$p[0],'handled_date'=>$p[1],'resolved_at'=>gmdate('Y-m-d H:i:s'),'cost'=>$p[2],'resolution_note'=>$p[3]]);
  if(str_contains($sql,"SET status='pending'"))$this->ticket=array_replace($this->ticket,['status'=>'pending','resolved_at'=>null]);
  if(str_starts_with($sql,'UPDATE ita_tickets SET cost='))$this->ticket['cost']=$p[0];
  if(str_starts_with($sql,'UPDATE ita_tickets SET technician_id='))$this->ticket['technician_id']=$p[0];
  return 1;
 }
}
$create=['operation'=>'create','asset_id'=>'7','type'=>'periodic','severity'=>'medium','note'=>'QA'];
foreach(['admin_it','system_manager','support_technician'] as $role){$db=new Db07();$db->role=$role;$service=new ItaTicketService($db,actor07($role));$service->save($create);ok07($db->asset['status']==='maintenance'&&count($db->events)===1,'all roles scoped create');rejects07(fn()=>$service->save($create),409);}
$db=new Db07();$db->role='support_technician';rejects07(fn()=>(new ItaTicketService($db,actor07('support_technician',[20])))->save($create),403);ok07(!$db->writes);
$db=new Db07();$service=new ItaTicketService($db,actor07());$service->save($create);
$input=fn(array $more)=>['id'=>9,'version'=>ItaAssetService::version($db->ticket),'note'=>'QA result']+$more;
$db->technicianDepartment=20;rejects07(fn()=>$service->save($input(['operation'=>'assign','technician_id'=>222])),403);
$db->technicianDepartment=10;$events=count($db->events);$service->save($input(['operation'=>'assign','technician_id'=>222]));ok07($db->ticket['technician_id']===222&&count($db->events)===$events,'assignment audit only, no same-state event');
$service->save($input(['operation'=>'transition','status'=>'resolved','handled_date'=>ItaTicketRules::today(),'cost'=>'100.25']));
ok07($db->asset['status']==='maintenance','unassigned stays maintenance');ok07($db->asset['next_maintenance_due']===ItaMaintenanceSchedule::next(ItaTicketRules::today(),6));
ok07(ItaTicketGuard::waiting($db,1,$db->asset),'awaiting admin');
$db->role='support_technician';rejects07(fn()=>(new ItaTicketService($db,actor07('support_technician')))->save($input(['operation'=>'cost','cost'=>'2'])),403);$db->role='admin_it';
rejects07(fn()=>$service->save($input(['operation'=>'transition','status'=>'pending'])),422);
$service->save($input(['operation'=>'cost','cost'=>'9999999999999999.99']));ok07($db->ticket['cost']==='9999999999999999.99','decimal no float');
$service->save(['operation'=>'ready','asset_id'=>7,'asset_version'=>ItaAssetService::version($db->asset),'note'=>'QA inspected']);ok07($db->asset['status']==='in_use');
$db=new Db07();$service=new ItaTicketService($db,actor07());$service->save(array_replace($create,['type'=>'repair']));
$service->save(['operation'=>'transition','id'=>9,'version'=>ItaAssetService::version($db->ticket),'status'=>'failed','handled_date'=>ItaTicketRules::today(),'cost'=>'10','note'=>'QA failed']);ok07($db->asset['status']==='broken');
$reopen=['operation'=>'transition','id'=>9,'version'=>ItaAssetService::version($db->ticket),'status'=>'pending','note'=>'QA reopen'];$db->otherPending=true;rejects07(fn()=>$service->save($reopen),409);$db->otherPending=false;$service->save($reopen);ok07($db->asset['status']==='maintenance');
$db->role='support_technician';$support=new ItaTicketService($db,actor07('support_technician'));
rejects07(fn()=>$support->save(['operation'=>'transition','id'=>9,'version'=>ItaAssetService::version($db->ticket),'status'=>'resolved','handled_date'=>ItaTicketRules::today(),'cost'=>'20','note'=>'Attempt cost bypass']),403);
$db->role='admin_it';$db->failAudit=true;$before=$db->ticket;
try{$service->save(['operation'=>'transition','id'=>9,'version'=>ItaAssetService::version($db->ticket),'status'=>'resolved','handled_date'=>ItaTicketRules::today(),'cost'=>'20','note'=>'QA']);throw new LogicException('Expected failure');}catch(RuntimeException $e){ok07($e->getMessage()==='QA audit failure');}ok07($db->rollback&&$db->ticket===$before);
foreach(['resolved'=>['pending','failed','resolved'],'failed'=>['resolved','failed'],'pending'=>['pending','other']] as $from=>$targets)foreach($targets as $to)rejects07(fn()=>ItaTicketRules::transition($from,$to),422);
ok07(ItaMaintenanceSchedule::next('2024-08-31',6)==='2025-02-28');ok07(ItaMaintenanceSchedule::next('2023-08-31',6)==='2024-02-29');ok07(ItaMaintenanceSchedule::effective(['purchase_date'=>'2026-01-31','maintenance_cycle_months'=>1])==='2026-02-28');ok07(ItaMaintenanceSchedule::effective([])===null);
ok07(!ItaTicketRules::sameCost('9999999999999999.99','9999999999999999.98'));
$db=new Db07();$old=$db->asset+['next_maintenance_due'=>'2026-07-31'];$old['next_maintenance_due']='2026-07-31';
$changed=ItaMaintenanceSchedule::initial($db,1,['category_id'=>2,'purchase_date'=>'2026-02-28'],$old);ok07($changed['next_maintenance_due']==='2026-08-28','corrected purchase recomputes initial schedule');
$old['last_maintenance_at']='2026-02-01 00:00:00';$changed=ItaMaintenanceSchedule::initial($db,1,['category_id'=>2,'purchase_date'=>'2026-02-28'],$old);ok07(!array_key_exists('next_maintenance_due',$changed),'preserve schedule after maintenance');
ok07(!str_contains(ItaTicketGuard::LATEST,'updated_at')&&str_contains(ItaTicketGuard::LATEST,'ita_ticket_events'),'cost/assign edits cannot reorder lifecycle');
$db=new Db07();(new ItaTicketObjects($db,actor07('support_technician')))->statistics(['year'=>'2026']);$query=end($db->reads);ok07(str_contains($query[0],'t.opened_at>=?')&&str_contains($query[0],'GROUP BY t.asset_id,a.asset_code,t.type,t.status')&&str_contains($query[0],'t.technician_id=?')&&$query[1]===[1,10,111,'2025-12-31 17:00:00','2026-12-31 17:00:00']);
$db=new Db07();$db->role='system_manager';$manager=new ItaTicketService($db,actor07('system_manager'));$manager->save($create);
rejects07(fn()=>$manager->save(['id'=>9,'version'=>ItaAssetService::version($db->ticket),'operation'=>'transition','status'=>'resolved','handled_date'=>ItaTicketRules::today(),'cost'=>'0','note'=>'QA']),403);
$db=new Db07();$db->asset['status']='retired';rejects07(fn()=>(new ItaTicketService($db,actor07()))->save($create),409);
$db->asset['status']='in_use';$db->asset['is_deleted']=1;rejects07(fn()=>(new ItaTicketService($db,actor07()))->save($create),404);
foreach (['-1','1.001','99999999999999999','1,000'] as $cost)rejects07(fn()=>ItaTicketRules::cost($cost),422);
rejects07(fn()=>ItaTicketRules::handled(['handled_date'=>'2000-01-01'],['opened_at'=>'2026-01-01 00:00:00']),422);
$future=(new DateTimeImmutable(ItaTicketRules::today()))->modify('+1 day')->format('Y-m-d');rejects07(fn()=>ItaTicketRules::handled(['handled_date'=>$future],['opened_at'=>'2026-01-01 00:00:00']),422);
$_SESSION['ita_csrf']='qa-token';$_SERVER['REQUEST_METHOD']='GET';$application=new ItaApplication();$reflection=new ReflectionClass($application);$reflection->getProperty('action')->setValue($application,'ticket-save');
rejects07(fn()=>$reflection->getMethod('api')->invoke($application),405);$_SERVER['REQUEST_METHOD']='POST';unset($_SERVER['HTTP_X_CSRF_TOKEN']);rejects07(fn()=>$reflection->getMethod('api')->invoke($application),403);
echo "$checks pure/scripted ticket/schedule checks passed; no DB.\n";
