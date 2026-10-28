<?php
/** Real service with fake transport. Never loads configuration or opens a DB. */
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
define('ITA_CAPABILITIES_ENABLED',true);
require __DIR__.'/../classes/security/itahttperror.class.php';
require __DIR__.'/../classes/security/itainput.class.php';
require __DIR__.'/../classes/dao/itauserobjectinfo.class.php';
class ItaDb
{
    public array $native=[],$writes=[],$permissions=[],$audits=[];
    public bool $auditFail=false,$commitFail=false,$activationFail=false,$duplicate=false,$extraRequired=false,$stale=false,$badScope=false,$revokeAfterAudit=false;
    public bool $began=false,$committed=false,$rolled=false;
    private function binds(string $sql,string $types,array $params): void { if (substr_count($sql,'?')!==count($params)||strlen($types)!==count($params))throw new RuntimeException('Invalid bind contract: '.$sql); }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        $this->binds($sql,$types,$params);
        if ($sql==='SHOW COLUMNS FROM dc_users') {
            $cols=[];foreach (['id','store_id','username','fullname','email','password','password_hash','type','status'] as $key) {
                $cols[]=['Field'=>$key,'Type'=>in_array($key,['id','store_id','status'],true)?'int(11)':($key==='password'?'varchar(32)':'varchar(255)'),
                    'Null'=>'NO','Default'=>null,'Extra'=>$key==='id'?'auto_increment':''];
            }
            if ($this->extraRequired)$cols[]=['Field'=>'unreviewed_required','Type'=>'varchar(20)','Null'=>'NO','Default'=>null,'Extra'=>''];
            return $cols;
        }
        if (str_starts_with($sql,'SELECT user_id,enabled,auth_revision'))return [['user_id'=>111,'enabled'=>1,'auth_revision'=>2]];
        if (str_starts_with($sql,'SELECT s.department_id'))return [];
        if (str_starts_with($sql,'SELECT permission_key')) { $keys=ItaPermissions::KEYS;sort($keys);return array_map(fn($k)=>['permission_key'=>$k],$keys); }
        if (str_starts_with($sql,'SELECT id FROM dc_users'))return $this->duplicate?[['id'=>8]]:[];
        if (str_starts_with($sql,'SELECT id FROM ita_departments'))return $this->badScope?[]:[['id'=>10]];
        throw new RuntimeException('Unexpected read: '.$sql);
    }
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        $this->binds($sql,$types,$params);
        if (str_starts_with($sql,'SELECT u.id,u.username,u.fullname,r.role'))return ($this->revokeAfterAudit&&$this->audits)?null:['id'=>111,'username'=>'MOCK-admin','fullname'=>'MOCK Admin','role'=>'admin_it','auth_revision'=>$this->stale?3:2];
        if ($sql==='SELECT LAST_INSERT_ID() id')return ['id'=>800];
        if (str_starts_with($sql,'SELECT id,status FROM dc_users'))return isset($this->native[800])?['id'=>800,'status'=>$this->native[800]['status']]:null;
        throw new RuntimeException('Unexpected readOne: '.$sql);
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        $this->binds($sql,$types,$params);$this->writes[]=$sql;
        if (str_starts_with($sql,'INSERT INTO dc_users')) { preg_match('/\(([^)]+)\)/',$sql,$m);$keys=explode('`,`',trim($m[1],'`'));$this->native[800]=array_combine($keys,$params);return 1; }
        if (str_starts_with($sql,'INSERT INTO ita_user_permissions')) { $this->permissions[]=$params[2];return 1; }
        if (str_starts_with($sql,'INSERT INTO ita_user_roles')||str_starts_with($sql,'INSERT INTO ita_user_scopes'))return 1;
        if (str_starts_with($sql,'INSERT INTO ita_audit_logs')) { if($this->auditFail)throw new RuntimeException('Synthetic audit fault');$this->audits[]=$params;return 1; }
        if (str_starts_with($sql,'UPDATE dc_users SET status=1')) { if(!$this->activationFail)$this->native[800]['status']=1;return 1; }
        if (str_starts_with($sql,'UPDATE dc_users SET status=0')) { $this->native[800]['status']=0;return 1; }
        throw new RuntimeException('Unexpected write: '.$sql);
    }
    public function beginTransaction(): void { $this->began=true; }
    public function commit(): void { if($this->commitFail)throw new RuntimeException('Synthetic commit fault');$this->committed=true; }
    public function rollBack(): void { $this->rolled=true;$this->permissions=[];$this->audits=[]; }
}
require __DIR__.'/../classes/dao/itauserobjects.class.php';
require __DIR__.'/../classes/services/itaauditservice.class.php';
require __DIR__.'/../classes/services/itausercreationservice.class.php';
$checks=0;
function ckCreate(bool $condition,string $message): void { global $checks;if(!$condition)throw new RuntimeException($message);$checks++; }
function denyCreate(callable $fn,int $status,string $label): void { try{$fn();}catch(ItaHttpError $e){ckCreate($e->status===$status,$label);return;}throw new RuntimeException('No denial: '.$label); }
function ownerCreate(?array $keys=null): ItaUserObjectInfo { return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'username'=>'MOCK-admin','fullname'=>'MOCK Admin','role'=>'admin_it','auth_revision'=>2,'permissions'=>$keys??ItaPermissions::KEYS]); }
function inputCreate(): array { return ['username'=>'MOCK-new','fullname'=>'MOCK Người thử','email'=>'mock-new@example.invalid','password'=>'MOCK-secret-12345','password_confirmation'=>'MOCK-secret-12345','permissions'=>['asset.read','asset.create'],'department_ids'=>[10]]; }
$input=inputCreate();$db=new ItaDb();$result=(new ItaUserCreationService($db,ownerCreate()))->create($input);
ckCreate($result===['id'=>800,'username'=>'MOCK-new'],'DTO has no password/hash');
ckCreate($db->committed && $db->native[800]['status']===1,'Activated native and committed mapping');
ckCreate(password_verify($input['password'],$db->native[800]['password_hash']),'New password modern hash works');
ckCreate($db->native[800]['password']!==md5($input['password']),'No MD5 of user password stored');
ckCreate($db->native[800]['type']==='1','Native type minimum staff string, never admin');
ckCreate($db->permissions===['asset.create','asset.read'],'Exact requested keys');
ckCreate(count($db->audits)===1 && $db->audits[0][2]==='user.create','Creation audited');
ckCreate(!str_contains(json_encode($db->audits),$input['password'])&&!str_contains(json_encode($db->audits),'password_hash'),'No credential in audit');
ckCreate(count(array_filter($db->writes,fn($s)=>str_starts_with($s,'INSERT INTO dc_users')))===1,'Native insertion once');
foreach (['username'=>'bad name','email'=>'not-email','fullname'=>'','password'=>'short','password_confirmation'=>'wrong','permissions'=>[],'department_ids'=>[]] as $key=>$value) {
    $db=new ItaDb();$bad=$input;$bad[$key]=$value;denyCreate(fn()=>(new ItaUserCreationService($db,ownerCreate()))->create($bad),422,'Invalid '.$key);ckCreate(!$db->writes,'No writes on invalid '.$key);
}
$invalidCases=[
 ['username','ab'],['username',str_repeat('a',51)],['username','name@example.invalid'],['username','-name'],['username','tên-mới'],['username',['forged']],
 ['fullname','   '],['fullname',"MOCK\nNAME"],['fullname',"MOCK\u{0085}NAME"],['fullname',str_repeat('a',101)],['fullname',['forged']],['fullname',"bad\0name"],
 ['email','plain-address'],['email','a@@example.invalid'],['email','a b@example.invalid'],['email',str_repeat('a',90).'@example.invalid'],['email',['forged']],
 ['password',null],['password',['forged']],['password',str_repeat(' ',12)],['password',"MOCK-secret\0-12345"],['password',str_repeat('a',73)],
 ['password_confirmation',['forged']],['permissions',['forged.root']],['permissions',['asset.create']],['permissions','asset.read'],['permissions',[1]],
 ['department_ids','10'],['department_ids',[0]],['department_ids',[-1]],['department_ids',['1.5']],['department_ids',[[10]]],['department_ids',array_fill(0,501,10)]
];
foreach ($invalidCases as $index=>[$key,$value]) {
    $db=new ItaDb();$bad=$input;$bad[$key]=$value;
    denyCreate(fn()=>(new ItaUserCreationService($db,ownerCreate()))->create($bad),422,'Invalid shape/boundary '.$index.' '.$key);
    ckCreate(!$db->began&&!$db->writes,'Malformed input denied before DB '.$index);
}
$normal=$input;$normal['username']='  mock.valid-1  ';$normal['email']=' mock.valid@example.invalid ';$normal['fullname']=' MOCK Họ Tên ';$normal['permissions']=['asset.read','asset.read'];$normal['department_ids']=[10,'10'];$normal['password']='   MOCK-password-123   ';$normal['password_confirmation']=$normal['password'];
$clean=ItaUserCreationInput::validate($normal,ownerCreate());ckCreate($clean['username']==='mock.valid-1'&&$clean['fullname']==='MOCK Họ Tên'&&$clean['email']==='mock.valid@example.invalid'&&$clean['password']===$normal['password'],'Trim identity fields without mutating password');
ckCreate($clean['department_ids']===[10]&&$clean['permissions']===['asset.read'],'Duplicate grants/scopes canonicalized');
$forged=$input+['store_id'=>999,'id'=>111,'role'=>'admin_it','type'=>9,'status'=>1,'enabled'=>false];$db=new ItaDb();
(new ItaUserCreationService($db,ownerCreate()))->create($forged);
ckCreate($db->native[800]['store_id']===1&&$db->native[800]['type']==='1','Client cannot forge native store/type');
ckCreate(str_contains(implode("\n",$db->writes),"'support_technician',1"),'Client cannot choose metadata admin role');
$db=new ItaDb();denyCreate(fn()=>(new ItaUserCreationService($db,ownerCreate([])))->create($input),403,'Role label cannot bypass access.manage');ckCreate(!$db->writes&&!$db->began,'Forbidden no write/transaction');
$db=new ItaDb();$restricted=ownerCreate(['scope.store','access.read','access.manage']);denyCreate(fn()=>(new ItaUserCreationService($db,$restricted))->create($input),403,'Cannot grant keys outside actor');
foreach (['duplicate'=>409,'stale'=>403,'badScope'=>422,'extraRequired'=>503] as $fault=>$status) {
    $db=new ItaDb();$db->$fault=true;denyCreate(fn()=>(new ItaUserCreationService($db,ownerCreate()))->create($input),$status,'Fail closed '.$fault);ckCreate(!$db->native,'No native insert '.$fault);
}
foreach (['auditFail','activationFail','commitFail','revokeAfterAudit'] as $fault) {
    $db=new ItaDb();$db->$fault=true;denyCreate(fn()=>(new ItaUserCreationService($db,ownerCreate()))->create($input),503,$fault.' controlled error');
    ckCreate($db->native[800]['status']===0,'MyISAM compensation '.$fault);ckCreate($db->rolled && !$db->permissions,'ITA rollback '.$fault);
    ckCreate(!array_filter($db->writes,fn($s)=>str_contains($s,'DELETE')),'Never delete native on fault');
}
$bad=$input;$bad['password']=str_repeat('é',37);$bad['password_confirmation']=$bad['password'];denyCreate(fn()=>ItaUserCreationInput::validate($bad,ownerCreate()),422,'UTF8 byte limit, not character count');
$unicode=$input;$unicode['password']=str_repeat('é',8);$unicode['password_confirmation']=$unicode['password'];ckCreate(ItaUserCreationInput::validate($unicode,ownerCreate())['password']===$unicode['password'],'Unicode password minimum is byte-based');
$blankName=$input;$blankName['fullname']=str_repeat("\u{00a0}",4);denyCreate(fn()=>ItaUserCreationInput::validate($blankName,ownerCreate()),422,'Unicode whitespace fullname denied');
$blankPassword=$input;$blankPassword['password']=str_repeat("\u{00a0}",12);$blankPassword['password_confirmation']=$blankPassword['password'];denyCreate(fn()=>ItaUserCreationInput::validate($blankPassword,ownerCreate()),422,'Unicode whitespace password denied');
$invisible=$input;$invisible['fullname']=str_repeat("\u{200b}",4);denyCreate(fn()=>ItaUserCreationInput::validate($invisible,ownerCreate()),422,'Format-only fullname denied');
$invisiblePassword=$input;$invisiblePassword['password']=str_repeat("\u{200b}",6);$invisiblePassword['password_confirmation']=$invisiblePassword['password'];denyCreate(fn()=>ItaUserCreationInput::validate($invisiblePassword,ownerCreate()),422,'Format-only password denied');
$global=$input;$global['permissions']=['asset.read','scope.store'];$global['department_ids']=[];ckCreate(ItaUserCreationInput::validate($global,ownerCreate())['department_ids']===[],'Explicit global scope permits empty department list');
$columns=(new ItaDb())->fetchAll('SHOW COLUMNS FROM dc_users');$columns[]= ['Field'=>'extra_optional','Type'=>'varchar(50)','Null'=>'YES','Default'=>null,'Extra'=>''];
$fields=ItaUserCreationInput::nativeFields($columns,$input+['store_id'=>1]);ckCreate(!array_key_exists('extra_optional',$fields),'Optional native columns use schema defaults');
$missing=array_values(array_filter($columns,fn($c)=>$c['Field']!=='password_hash'));denyCreate(fn()=>ItaUserCreationInput::nativeFields($missing,$input+['store_id'=>1]),503,'Missing modern-hash column fail closed');
$noAuto=$columns;$noAuto[0]['Extra']='';denyCreate(fn()=>ItaUserCreationInput::nativeFields($noAuto,$input+['store_id'=>1]),503,'Native ID without auto increment rejected');
$small=$columns;foreach($small as &$column){if($column['Field']==='username')$column['Type']='varchar(3)';}unset($column);
denyCreate(fn()=>ItaUserCreationInput::nativeFields($small,$input+['store_id'=>1]),422,'Actual shorter schema width enforced');
require __DIR__.'/../includes/ita_mode.inc.php';ckCreate(itaRequestDecision('/admin.php',['op'=>'ita','act'=>'user-create'],'POST',['enabled'=>true,'legacy_enabled'=>false])==='shell','POST gate allows approved endpoint');
require __DIR__.'/../classes/security/itasession.class.php';
require __DIR__.'/../classes/services/itaapplication.class.php';
$app=new ItaApplication();$method=new ReflectionMethod($app,'api');(new ReflectionProperty($app,'action'))->setValue($app,'user-create');
$_SERVER['REQUEST_METHOD']='GET';denyCreate(fn()=>$method->invoke($app),405,'GET cannot create native account');
$_SERVER['REQUEST_METHOD']='POST';$_SESSION['ita_csrf']='synthetic-token';unset($_SERVER['HTTP_X_CSRF_TOKEN']);$_POST=[];
denyCreate(fn()=>$method->invoke($app),403,'Missing CSRF denied by actual route before service/db');
$_SERVER['HTTP_X_CSRF_TOKEN']='wrong';denyCreate(fn()=>$method->invoke($app),403,'Wrong CSRF denied by actual route');
echo "PASS: $checks account creation checks, real service/fake transport; no DB.\n";
