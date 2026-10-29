<?php
/** Actual account services, synthetic transport only; no configuration or DB connection. */
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
define('ITA_CAPABILITIES_ENABLED',true);
foreach (['security/itahttperror','security/itainput','dao/itauserobjectinfo','dao/itauserobjects','security/italoginlimiter',
    'services/itaauditservice','services/italicensereveal','services/itauserprofileservice','services/itacapabilityaccessservice','security/itasession','services/itaapplication'] as $file) {
    require_once __DIR__.'/../classes/'.$file.'.class.php';
}
class ItaDb
{
    public array $native=[],$roles=[],$permissions=[],$audits=[],$writes=[],$limits=[];
    public bool $duplicate=false,$auditFail=false,$commitFail=false,$revisionFail=false,$casFail=false,$actorChanged=false,$shortHashColumn=false,$missingOrigin=false;
    public int $begins=0,$rollbacks=0;private array $before=[];
    public function __construct()
    {
        $this->native=[1=>['id'=>1,'username'=>'mock-admin','fullname'=>'MOCK Admin','email'=>'admin@example.invalid','password'=>md5('admin-fixture'),'password_hash'=>password_hash('admin-fixture',PASSWORD_DEFAULT),'status'=>1,'type'=>1],
            2=>['id'=>2,'username'=>'mock-user','fullname'=>'MOCK User','email'=>'user@example.invalid','password'=>md5('old-fixture'),'password_hash'=>'','status'=>1,'type'=>1]];
        $this->roles=[1=>['user_id'=>1,'role'=>'admin_it','enabled'=>1,'auth_revision'=>3],2=>['user_id'=>2,'role'=>'support_technician','enabled'=>1,'auth_revision'=>1]];
        $this->permissions=[1=>ItaPermissions::KEYS,2=>['asset.read']];
    }
    private function binds(string $sql,string $types,array $params): void
    { if(substr_count($sql,'?')!==count($params)||strlen($types)!==count($params))throw new RuntimeException('Bind contract mismatch'); }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        $this->binds($sql,$types,$params);$id=$params[1]??1;
        if($sql==='SHOW COLUMNS FROM dc_users')return array_map(fn($field)=>['Field'=>$field,'Type'=>'varchar('.($field==='password_hash'?($this->shortHashColumn?20:255):50).')'],['username','fullname','email','password','password_hash']);
        if(str_starts_with($sql,'SELECT user_id,enabled,auth_revision'))return array_values($this->roles);
        if(str_starts_with($sql,'SELECT s.department_id')||str_starts_with($sql,'SELECT department_id'))return $id===2?[['department_id'=>10]]:[];
        if(str_starts_with($sql,'SELECT permission_key')){$keys=$this->permissions[$id]??[];sort($keys);return array_map(fn($key)=>['permission_key'=>$key],$keys);}
        if(str_starts_with($sql,'SELECT id FROM dc_users'))return $this->duplicate?[['id'=>9]]:[];
        if(str_starts_with($sql,'SELECT p.user_id'))return [['user_id'=>1]];
        throw new RuntimeException('Unexpected synthetic list query');
    }
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        $this->binds($sql,$types,$params);$id=(int)($params[1]??1);$u=$this->native[$id]??null;$r=$this->roles[$id]??null;
        if(str_starts_with($sql,'SELECT id FROM ita_audit_logs'))return $this->missingOrigin?null:['id'=>100];
        if(str_contains($sql,'FROM ita_login_limits'))return ['attempts'=>$this->limits[$params[1]]??0,'age'=>0];
        if(($params[0]??1)!==1)return null;
        if(str_starts_with($sql,'SELECT u.id,u.username,u.fullname,r.role'))return $u&&$r&&$u['status']===1&&$r['enabled']===1?$u+$r:null;
        if(str_starts_with($sql,'SELECT u.id,u.username,u.status,r.role'))return $u?$u+($r??['role'=>null,'enabled'=>null]):null;
        if(str_starts_with($sql,'SELECT auth_revision')||str_starts_with($sql,'SELECT enabled,auth_revision'))return $r;
        if(str_starts_with($sql,'SELECT id,username,fullname,email'))return $u;
        if(str_starts_with($sql,'SELECT password,password_hash')){
            if($this->actorChanged&&$this->audits&&$id===1)return ['password'=>'changed','password_hash'=>'changed'];
            return $u&&$u['status']===1?['password'=>$u['password'],'password_hash'=>$u['password_hash']]:null;
        }
        throw new RuntimeException('Unexpected synthetic single query');
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        $this->binds($sql,$types,$params);$this->writes[]=$sql;
        if(str_starts_with($sql,'INSERT INTO ita_login_limits')){$this->limits[$params[1]]??=0;return 1;}
        if(str_starts_with($sql,'UPDATE ita_login_limits')){if(str_contains($sql,'attempts=attempts+1'))$this->limits[$params[1]]++;else $this->limits[$params[1]]=0;return 1;}
        if(str_starts_with($sql,'INSERT INTO ita_audit_logs')){if($this->auditFail)throw new RuntimeException('Synthetic audit fault');$this->audits[]=$params;return 1;}
        if(str_starts_with($sql,'UPDATE dc_users SET username')){
            if($this->casFail)return 0;$id=$params[4];$this->native[$id]['username']=$params[0];$this->native[$id]['fullname']=$params[1];$this->native[$id]['email']=$params[2];return 1;
        }
        if(str_starts_with($sql,'UPDATE dc_users SET password_hash')){
            if($this->casFail)return 0;$id=$params[3];$this->native[$id]['password_hash']=$params[0];$this->native[$id]['password']=$params[1];return 1;
        }
        if(str_starts_with($sql,'UPDATE ita_user_roles SET auth_revision')){if($this->revisionFail)throw new RuntimeException('Synthetic revision fault');$this->roles[$params[2]]['auth_revision']++;return 1;}
        if(str_starts_with($sql,'UPDATE ita_user_roles SET enabled=0')){$this->roles[$params[2]]['enabled']=0;$this->roles[$params[2]]['auth_revision']++;return 1;}
        throw new RuntimeException('Unexpected synthetic write');
    }
    public function beginTransaction(): void {$this->begins++;$this->before=[$this->roles,$this->audits,$this->limits];}
    public function commit(): void {if($this->commitFail&&$this->audits)throw new RuntimeException('Synthetic commit fault');}
    public function rollBack(): void {$this->rollbacks++;[$this->roles,$this->audits,$this->limits]=$this->before;}
}
$checks=0;
function ckProfile(bool $ok,string $label): void {global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
function rejectProfile(callable $fn,int $status): void {try{$fn();throw new RuntimeException('Expected denial');}catch(ItaHttpError $e){ckProfile($e->status===$status,'Expected status '.$status.' got '.$e->status);}}
function profileActor(?array $keys=null): ItaUserObjectInfo {return new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'username'=>'mock-admin','fullname'=>'MOCK Admin','role'=>'admin_it','auth_revision'=>3,'permissions'=>$keys??ItaPermissions::KEYS]);}
function profileService(ItaDb $db,?ItaUserObjectInfo $actor=null): ItaUserProfileService {return new ItaUserProfileService($db,$actor??profileActor(),['auth_key'=>'synthetic-profile-hmac-only']);}
function resetInput(ItaUserProfileService $service): array {return ['user_id'=>2,'version'=>$service->read(2)['version'],'password'=>'new123','password_confirmation'=>'new123','actor_password'=>'admin-fixture'];}
$db=new ItaDb();$s=profileService($db);$dto=$s->read(2);
ckProfile(!array_key_exists('password',$dto)&&!array_key_exists('password_hash',$dto)&&strlen($dto['version'])===64,'Read DTO excludes credentials');
$input=resetInput($s);$old=$db->native[2]['password'];$s->resetPassword($input,'192.0.2.1');
ckProfile(password_verify('new123',$db->native[2]['password_hash']),'New password verifies');
ckProfile(!ItaLicenseReveal::verify($db->native[2],'old-fixture')&&!hash_equals($old,$db->native[2]['password']),'Old password and MD5 no longer match');
ckProfile($db->native[2]['password']!==md5('new123')&&preg_match('/^[a-f0-9]{32}$/D',$db->native[2]['password'])===1,'Legacy field is random placeholder, not new password MD5');
ckProfile($db->roles[2]['auth_revision']===2,'Reset invalidates ITA session revision');
ckProfile(!str_contains(json_encode($db->audits),'new123')&&!str_contains(json_encode($db->audits),'admin-fixture')&&!str_contains(json_encode($db->audits),'password_hash'),'Audit contains no credentials');
rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),409);
foreach(['wrong-admin','short','mismatch','blank','long','nul','type','missing'] as $case){
    $db=new ItaDb();$s=profileService($db);$input=resetInput($s);$expected=422;
    if($case==='wrong-admin'){$input['actor_password']='wrong';$expected=401;}
    if($case==='short')$input['password']=$input['password_confirmation']='12345';
    if($case==='mismatch')$input['password_confirmation']='different';
    if($case==='blank')$input['password']=$input['password_confirmation']=str_repeat(' ',6);
    if($case==='long')$input['password']=$input['password_confirmation']=str_repeat('é',37);
    if($case==='nul')$input['password']=$input['password_confirmation']="abc\0def";
    if($case==='type'){$db->native[2]['type']=3;$expected=403;}
    if($case==='missing'){unset($db->native[2]);$expected=404;}
    $before=$db->native;rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),$expected);ckProfile($before===$db->native,'Rejected '.$case.' leaves native unchanged');
}
$db=new ItaDb();$s=profileService($db,profileActor(['access.read','scope.store']));rejectProfile(fn()=>$s->read(2),403);
$db=new ItaDb();$s=profileService($db);$input=resetInput($s);$db->actorChanged=true;rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),403);ckProfile($db->native[2]['password_hash']==='','Reauth changed before mutation blocks reset');
foreach(['auditFail','casFail','revisionFail','commitFail'] as $fault){
    $db=new ItaDb();$s=profileService($db);$input=resetInput($s);$db->$fault=true;
    if($fault==='auditFail'){try{$s->resetPassword($input,'192.0.2.1');throw new RuntimeException('Missing audit fault');}catch(RuntimeException $e){ckProfile($e->getMessage()==='Synthetic audit fault','Audit fault surfaces');}}
    else rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),$fault==='casFail'?409:503);
    ckProfile(($db->native[2]['password_hash']!=='')===in_array($fault,['revisionFail','commitFail'],true),'MyISAM state retained correctly for '.$fault);
    ckProfile($db->roles[2]['auth_revision']===1&&!$db->audits,'ITA state rolled back for '.$fault);
}
$db=new ItaDb();$s=profileService($db);$input=['user_id'=>2,'version'=>$s->read(2)['version'],'username'=>'mock-renamed','fullname'=>'MOCK Changed','email'=>'changed@example.invalid'];$s->update($input);
ckProfile($db->native[2]['username']==='mock-renamed'&&$db->native[2]['email']==='changed@example.invalid','Profile changes persist in synthetic native transport');
ckProfile($db->native[2]['password_hash']==='','Profile edit never changes credential');rejectProfile(fn()=>$s->update($input),409);
$db=new ItaDb();$s=profileService($db);$input['version']=$s->read(2)['version'];$db->duplicate=true;rejectProfile(fn()=>$s->update($input),409);
$db=new ItaDb();$s=profileService($db);$input['version']=$s->read(2)['version'];$input['fullname']=str_repeat('a',51);rejectProfile(fn()=>$s->update($input),422);
$db=new ItaDb();$owner=profileActor();$access=(new ItaUserObjects($db))->access($owner,2);(new ItaCapabilityAccessService($db,$owner))->revoke(['user_id'=>2,'version'=>$access['version']]);
ckProfile($db->roles[2]['enabled']===0&&$db->native[2]['status']===1,'Revoke changes ITA only');
ckProfile($db->permissions[2]===['asset.read'],'Revoke leaves permissions intact');
$db=new ItaDb();$access=(new ItaUserObjects($db))->access($owner,1);rejectProfile(fn()=>(new ItaCapabilityAccessService($db,$owner))->revoke(['user_id'=>1,'version'=>$access['version']]),409);
$db=new ItaDb();$s=profileService($db);$input=resetInput($s);$db->shortHashColumn=true;rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),503);ckProfile($db->native[2]['password_hash']==='','Insufficient hash column refuses before native write');
$db=new ItaDb();$s=profileService($db);$input=resetInput($s);$input['actor_password']='wrong';
for($i=0;$i<5;$i++)rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),401);
rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),429);ckProfile($db->native[2]['password_hash']==='','Limiter prevents native mutation');
$db=new ItaDb();$s=profileService($db);$input=resetInput($s);$db->roles[1]['auth_revision']++;rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),403);ckProfile($db->native[2]['password_hash']==='','Stale actor revision cannot reset');
$db=new ItaDb();$s=profileService($db);$input=resetInput($s);$input['password']=$input['password_confirmation']='old-fixture';rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),422);ckProfile($db->native[2]['password_hash']===''&&!$db->audits,'Same password is rejected without native change or success audit');
$db=new ItaDb();$limited=profileActor(['access.read','access.manage','scope.store']);rejectProfile(fn()=>profileService($db,$limited)->read(2),403);
foreach(['user-profile-save','user-password-reset','access-revoke'] as $action){
    $db=new ItaDb();$app=(new ReflectionClass(ItaApplication::class))->newInstanceWithoutConstructor();
    foreach(['db'=>$db,'user'=>profileActor(),'config'=>['auth_key'=>'synthetic-profile-hmac-only'],'action'=>$action] as $key=>$value)(new ReflectionProperty(ItaApplication::class,$key))->setValue($app,$value);
    $method=new ReflectionMethod(ItaApplication::class,'api');$_SERVER=['REQUEST_METHOD'=>'GET'];rejectProfile(fn()=>$method->invoke($app),405);
    $_SESSION=['ita_csrf'=>'synthetic-csrf'];$_SERVER=['REQUEST_METHOD'=>'POST','HTTP_X_CSRF_TOKEN'=>'invalid'];rejectProfile(fn()=>$method->invoke($app),403);
    ckProfile(!$db->writes&&$db->begins===0,'Method/CSRF rejected before native or transaction');
}
$db=new ItaDb();$s=profileService($db);$db->missingOrigin=true;rejectProfile(fn()=>$s->read(2),403);ckProfile(!$db->writes,'Existing mapped account without creation audit is protected');
$db=new ItaDb();$db->native[2]['password_hash']=password_hash('modern-fixture',PASSWORD_DEFAULT);$s=profileService($db);$input=resetInput($s);$input['password']=$input['password_confirmation']='old-fixture';rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),422);ckProfile(!$db->audits,'Distinct old MD5 password cannot be reused');
$db=new ItaDb();$db->roles[2]['auth_revision']=4294967295;$s=profileService($db);$input=resetInput($s);rejectProfile(fn()=>$s->resetPassword($input,'192.0.2.1'),503);ckProfile($db->native[2]['password_hash']==='','Revision overflow denied before native write');
$access=(new ItaUserObjects($db))->access(profileActor(),2);rejectProfile(fn()=>(new ItaCapabilityAccessService($db,profileActor()))->revoke(['user_id'=>2,'version'=>$access['version']]),503);ckProfile($db->roles[2]['enabled']===1,'Revoke overflow leaves access unchanged');
echo "$checks profile/password/revoke service checks passed; synthetic transport, no DB or atomicity proof.\n";
