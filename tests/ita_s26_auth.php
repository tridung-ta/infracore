<?php
/** S26 counters: actual ItaAuthService + instrumented actual PmAuth, fake DB only. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('ROOT_PATH',dirname(__DIR__).'/');
$checks=0;
function s26Check(bool $ok,string $label): void {global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
class S26DownstreamSpy
{
    public static int $construct=0,$authenticate=0,$verify=0;
    public static function verify(string $password,string $hash): bool {self::$verify++;return password_verify($password,$hash);}
    public static function reset(): void {self::$construct=self::$authenticate=self::$verify=0;}
}
class S26ServiceCalls
{
    public static int $verify=0,$hash=0;
    public static function verify(string $password,string $hash): bool {self::$verify++;return password_verify($password,$hash);}
    public static function hash(string $password,string|int|null $algorithm,array $options=[]): string {self::$hash++;return password_hash($password,$algorithm,$options);}
}
// Trusted repository source only, in-memory instrumentation; PmAuth on disk stays untouched.
$original=file_get_contents(ROOT_PATH.'classes/security/pmauth.class.php');
$instrumented=str_replace('password_verify(', 'S26DownstreamSpy::verify(', $original, $verifySites);
s26Check($verifySites===1,'PmAuth verifier instrumentation covers its one real call site');
$instrumented=str_replace('public function __construct($database) {','public function __construct($database) { S26DownstreamSpy::$construct++;',$instrumented,$constructSites);
$instrumented=str_replace('public function authenticate(int $storeId, string $identity, string $password): int {','public function authenticate(int $storeId, string $identity, string $password): int { S26DownstreamSpy::$authenticate++;',$instrumented,$authSites);
s26Check($constructSites===1&&$authSites===1,'PmAuth method counters instrumented');
eval(substr($instrumented,5));
$serviceOriginal=file_get_contents(ROOT_PATH.'classes/services/itaauthservice.class.php');
$serviceInstrumented=str_replace('password_verify(', 'S26ServiceCalls::verify(', $serviceOriginal, $serviceVerifySites);
$serviceInstrumented=str_replace('password_hash($password,', 'S26ServiceCalls::hash($password,', $serviceInstrumented, $serviceHashSites);
s26Check($serviceVerifySites===1&&$serviceHashSites===1,'all service verify/hash call sites instrumented');
eval(substr($serviceInstrumented,5));
$bootstrap=file_get_contents(ROOT_PATH.'includes/ita_bootstrap.inc.php');
$list=explode('] as $itaClassFile)',explode('foreach ([',$bootstrap,2)[1],2)[0];
preg_match_all("/'([a-z0-9\/]+)'/",$list,$classFiles);
s26Check(count($classFiles[1])>30,'Actual bootstrap class list loaded, instrumented PmAuth kept');
foreach($classFiles[1] as $file){if(!in_array($file,['security/pmauth','services/itaauthservice'],true))require_once ROOT_PATH.'classes/'.$file.'.class.php';}
class S26LegacyTransport extends PmDb
{
    public array $row=[],$writes=[];
    public function __construct(){}
    public function fetchAll(string $sql,string $types='',array $params=[]): array {return [$this->row];}
    public function execute(string $sql,string $types='',array $params=[]): int {$this->writes[]=[$sql,$params];return 1;}
}
$pw='QA-S26-only-password';$bcrypt=password_hash($pw,PASSWORD_DEFAULT);
// Positive controls prove downstream counters fire, rather than asserting an unused stub's zero.
try{new PmAuth((object)[]);}catch(RuntimeException){}
s26Check(S26DownstreamSpy::$construct===1,'constructor counter positive control, no connection');
$legacy=new S26LegacyTransport();$legacy->row=['id'=>99,'status'=>1,'password'=>'','password_hash'=>$bcrypt];
$pm=(new ReflectionClass(PmAuth::class))->newInstanceWithoutConstructor();(new ReflectionProperty(PmAuth::class,'database'))->setValue($pm,$legacy);
s26Check($pm->authenticate(1,'qa',$pw)===99&&S26DownstreamSpy::$authenticate===1&&S26DownstreamSpy::$verify===1,'actual downstream authenticate/verify positive control');
S26DownstreamSpy::reset();
class S26Db extends ItaDb
{
    public string $kind='md5';
    public bool $active=true,$mapping=true,$failIdentity=false,$failAudit=false,$failNative=false,$blocked=false,$flipScopeOrder=false;
    public int $revision=3,$identities=1,$currentReads=0;
    public array $credential,$writes=[],$calls=[],$attempts=[];
    public function __construct(array $credential){$this->credential=$credential;}
    public function beginTransaction(): void{}
    public function commit(): void{}
    public function rollBack(): void{}
    private function log(string $sql,string $types,array $params): void {s26Check(strlen($types)===count($params),'SQL bind count');$this->calls[]=[$sql,$types,$params];}
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        $this->log($sql,$types,$params);
        if(str_starts_with($sql,'SELECT id,username,status')){
            if($this->failIdentity)throw new RuntimeException('QA transport unavailable');
            return array_fill(0,$this->identities,['id'=>99,'username'=>'qa','status'=>$this->active?1:0]);
        }
        if(str_starts_with($sql,'SELECT s.department_id'))return $this->flipScopeOrder&&$this->currentReads>1?[['department_id'=>20],['department_id'=>10]]:[['department_id'=>10],['department_id'=>20]];
        throw new RuntimeException('Unexpected list query');
    }
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        $this->log($sql,$types,$params);
        if(str_contains($sql,'FROM ita_login_limits'))return ['attempts'=>$this->blocked?30:($this->attempts[$params[1]]??0),'age'=>0];
        if(str_starts_with($sql,'SELECT u.id,u.username,u.fullname')){
            $this->currentReads++;
            return $this->active&&$this->mapping?['id'=>99,'username'=>'qa','fullname'=>'QA','role'=>'system_manager','auth_revision'=>$this->revision]:null;
        }
        if(str_starts_with($sql,'SELECT password,password_hash'))return $this->active?$this->credential:null;
        throw new RuntimeException('Unexpected single query');
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        $this->log($sql,$types,$params);
        if(str_starts_with($sql,'UPDATE dc_users')&&$this->failNative)throw new RuntimeException('QA native unavailable');
        if(str_starts_with($sql,'INSERT INTO ita_audit_logs')&&$this->failAudit)throw new RuntimeException('QA audit unavailable');
        $this->writes[]=[$sql,$types,$params];
        if(str_contains($sql,'SET attempts=attempts+1'))$this->attempts[$params[1]]=($this->attempts[$params[1]]??0)+1;
        if(str_contains($sql,'SET attempts=0'))$this->attempts[$params[1]]=0;
        return 1;
    }
    public function nativeWrites(): array {return array_values(array_filter($this->writes,fn($w)=>str_starts_with($w[0],'UPDATE dc_users')));}
}
class S26Service extends ItaAuthService
{
    public array $verifies=[];
    public $afterVerify;
    protected function verifyHash(string $password,string $hash): bool {
        $this->verifies[]=$hash;$result=parent::verifyHash($password,$hash);
        if($this->afterVerify)($this->afterVerify)();return $result;
    }
    public function dummyForTest(): string {return parent::dummyHash();}
}
function s26Service(S26Db $db): S26Service {return new S26Service($db,['store_id'=>1,'auth_key'=>'QA-synthetic-HMAC-key']);}
function s26NoPm(): void {s26Check(S26DownstreamSpy::$construct===0&&S26DownstreamSpy::$authenticate===0&&S26DownstreamSpy::$verify===0,'No downstream constructor/authenticate/verify in ITA flow');}
$md5=['password'=>md5($pw),'password_hash'=>''];$modern=['password'=>md5($pw),'password_hash'=>$bcrypt];
$probe=s26Service(new S26Db($md5));$dummy=$probe->dummyForTest();
s26Check(password_get_info($dummy)===password_get_info($bcrypt)&&!password_needs_rehash($dummy,PASSWORD_DEFAULT),'dummy algorithm/cost match unchanged PmAuth default');
s26Check($dummy===$probe->dummyForTest()&&$dummy===s26Service(new S26Db($md5))->dummyForTest(),'dummy fixed across calls/instances');
$messages=[];
foreach(['missing','ambiguous','disabled','mapping','md5-wrong','bcrypt-wrong','malformed','native-disappears','mapping-revoked','revision-revoked','identity-ambiguous-after-verify','credential-changed'] as $case){
    $beforeVerify=S26ServiceCalls::$verify;$beforeHash=S26ServiceCalls::$hash;
    $db=new S26Db($case==='bcrypt-wrong'?$modern:$md5);$svc=s26Service($db);$password='wrong';
    if($case==='missing')$db->identities=0;
    if($case==='ambiguous')$db->identities=2;
    if($case==='disabled'){$db->active=false;$password=$pw;}
    if($case==='mapping'){$db->mapping=false;$password=$pw;}
    if($case==='malformed')$db->credential['password_hash']='invalid-native-hash';
    if(in_array($case,['native-disappears','mapping-revoked','revision-revoked','identity-ambiguous-after-verify','credential-changed'])){
        $password=$pw;
        $svc->afterVerify=function()use($db,$case){
            if($case==='native-disappears')$db->active=false;
            if($case==='mapping-revoked')$db->mapping=false;
            if($case==='revision-revoked')$db->revision++;
            if($case==='identity-ambiguous-after-verify')$db->identities=2;
            if($case==='credential-changed')$db->credential['password']=md5('other');
        };
    }
    try{$svc->authenticate('qa',$password,'192.0.2.1');throw new RuntimeException('Expected401 '.$case);}
    catch(ItaHttpError $e){s26Check($e->status===401,'401 '.$case);$messages[]=$e->getMessage();}
    s26Check(count($svc->verifies)===1&&count($db->nativeWrites())===0,'one verify / zero native writes '.$case);
    s26Check($svc->verifies[0]===($case==='bcrypt-wrong'?$bcrypt:$dummy),'real/dummy selection '.$case);s26NoPm();
    s26Check(S26ServiceCalls::$verify-$beforeVerify===1&&S26ServiceCalls::$hash===$beforeHash,'actual service function count401 '.$case);
}
s26Check(count(array_unique($messages))===1,'All401 share identical message');
foreach(['a','b','x'] as $variant){
    $hash='$2'.$variant.substr($bcrypt,3);
    s26Check(password_verify($pw,$hash),'PHP accepts legacy bcrypt variant '.$variant);
    foreach([true,false] as $correct){
        $db=new S26Db(['password'=>md5($pw),'password_hash'=>$hash]);$svc=s26Service($db);$beforeVerify=S26ServiceCalls::$verify;
        try{$svc->authenticate('qa',$correct?$pw:'wrong','192.0.2.1');s26Check($correct&&count($db->nativeWrites())===1,'supported bcrypt variant success '.$variant);}
        catch(ItaHttpError $e){s26Check(!$correct&&$e->status===401&&!$db->nativeWrites(),'supported bcrypt variant failure '.$variant);}
        s26Check(S26ServiceCalls::$verify-$beforeVerify===1&&$svc->verifies===[$hash],'legacy bcrypt variant real verify once '.$variant);s26NoPm();
    }
}
// Knowing the public dummy input must not authenticate a missing user.
$db=new S26Db($md5);$db->identities=0;$svc=s26Service($db);
try{$svc->authenticate('qa','synthetic-S26-dummy-not-a-credential','192.0.2.1');}catch(ItaHttpError $e){s26Check($e->status===401&&count($svc->verifies)===1&&!$db->nativeWrites(),'dummy verify result never grants access');}
foreach(['md5','bcrypt','old-cost','long-md5','scope-order'] as $case){
    $beforeVerify=S26ServiceCalls::$verify;$beforeHash=S26ServiceCalls::$hash;
    $password=$case==='long-md5'?str_repeat('x',80).'tail':$pw;
    $credentials=$case==='bcrypt'||$case==='scope-order'?$modern:['password'=>md5($password),'password_hash'=>''];
    if($case==='old-cost')$credentials['password_hash']=password_hash($pw,PASSWORD_BCRYPT,['cost'=>4]);
    $db=new S26Db($credentials);$db->flipScopeOrder=$case==='scope-order';$svc=s26Service($db);$user=$svc->authenticate('qa',$password,'192.0.2.1');
    s26Check($user->id===99&&count($svc->verifies)===1&&count($db->nativeWrites())===1,'successful login '.$case);s26NoPm();
    $write=$db->nativeWrites()[0];$upgrade=in_array($case,['md5','old-cost']);
    s26Check(str_contains($write[0],'password_hash = ?')===$upgrade,'upgrade only when expected '.$case);
    if($upgrade)s26Check(password_verify($password,$write[2][0])&&password_get_info($write[2][0])===password_get_info($bcrypt),'upgrade PmAuth-compatible hash');
    s26Check(!str_contains($write[0],'SET password =')&&str_contains($write[0],'last_login'),'MD5 preserved, last_login written');
    $audit=array_values(array_filter($db->writes,fn($w)=>str_starts_with($w[0],'INSERT INTO ita_audit_logs')));
    s26Check(count($audit)===1&&$audit[0][2][2]==='auth.login','successful login audit '.$case);
    s26Check(S26ServiceCalls::$verify-$beforeVerify===1&&S26ServiceCalls::$hash-$beforeHash===($upgrade?1:0),'actual success verify/hash count '.$case);
}
// Correct bcrypt credential denied after verification must also be native-write-free.
// The six-character creation policy must remain compatible with real ITA login.
$shortPassword='qa1234';$shortHash=password_hash($shortPassword,PASSWORD_DEFAULT);
$db=new S26Db(['password'=>str_repeat('a',32),'password_hash'=>$shortHash]);$svc=s26Service($db);
$shortUser=$svc->authenticate('qa',$shortPassword,'192.0.2.1');
s26Check($shortUser->id===99&&$svc->verifies===[$shortHash]&&count($db->nativeWrites())===1,'six-character modern password authenticates and records login');s26NoPm();
try{(s26Service(new S26Db(['password'=>str_repeat('a',32),'password_hash'=>$shortHash])))->authenticate('qa','wrong6','192.0.2.1');throw new RuntimeException('Expected 401');}
catch(ItaHttpError $e){s26Check($e->status===401,'six-character account rejects wrong password');}
foreach(['disable','mapping','identity','credential'] as $revoke){
    $db=new S26Db($modern);$svc=s26Service($db);$beforeVerify=S26ServiceCalls::$verify;$beforeHash=S26ServiceCalls::$hash;
    $svc->afterVerify=function()use($db,$revoke){
        if($revoke==='disable')$db->active=false;
        if($revoke==='mapping')$db->mapping=false;
        if($revoke==='identity')$db->identities=2;
        if($revoke==='credential')$db->credential['password_hash']='changed';
    };
    try{$svc->authenticate('qa',$pw,'192.0.2.1');throw new RuntimeException('Expected bcrypt revoke401');}
    catch(ItaHttpError $e){s26Check($e->status===401&&$e->getMessage()===$messages[0],'correct bcrypt denied has same401');}
    s26Check(S26ServiceCalls::$verify-$beforeVerify===1&&S26ServiceCalls::$hash===$beforeHash&&$svc->verifies===[$bcrypt]&&!$db->nativeWrites(),'correct bcrypt revoke one real verify/no native');s26NoPm();
}
foreach([['password'=>md5(str_repeat('x',80).'tail'),'password_hash'=>''],['password'=>'','password_hash'=>password_hash(str_repeat('x',72),PASSWORD_DEFAULT)]] as $credentials){
    $db=new S26Db($credentials);$svc=s26Service($db);
    try{$svc->authenticate('qa',str_repeat('x',80).'different-tail','192.0.2.1');throw new RuntimeException('Expected long rejection');}
    catch(ItaHttpError $e){s26Check($e->status===401&&count($svc->verifies)===1&&$svc->verifies[0]===$dummy&&!$db->nativeWrites(),'long MD5 suffix / bcrypt truncation rejected with one dummy');}s26NoPm();
}
if(defined('PASSWORD_ARGON2ID')){
    $long=str_repeat('a',80).'argon-tail';$hash=password_hash($long,PASSWORD_ARGON2ID);
    foreach([true,false] as $correct){$db=new S26Db(['password'=>'','password_hash'=>$hash]);$svc=s26Service($db);
        try{$svc->authenticate('qa',$correct?$long:'bad','192.0.2.1');s26Check($correct&&count($db->nativeWrites())===1&&!str_contains($db->nativeWrites()[0][0],'password_hash'),'long Argon success retained');}
        catch(ItaHttpError $e){s26Check(!$correct&&$e->status===401&&!$db->nativeWrites(),'Argon failure exception401/no write');}
        s26Check(count($svc->verifies)===1&&$svc->verifies[0]===$hash,'Argon real verify only, no dummy');s26NoPm();
    }
}else{echo "SKIP: runtime lacks Argon2id; no claim of Argon2 execution.\n";}
foreach(['invalid','limited','transport'] as $case){
    $beforeVerify=S26ServiceCalls::$verify;$beforeHash=S26ServiceCalls::$hash;
    $db=new S26Db($md5);$svc=s26Service($db);$db->blocked=$case==='limited';$db->failIdentity=$case==='transport';
    try{$svc->authenticate('qa',$case==='invalid'?'':$pw,'192.0.2.1');throw new LogicException('Expected rejection');}
    catch(ItaHttpError $e){s26Check($e->status===($case==='invalid'?422:429),'guard status');}
    catch(RuntimeException $e){s26Check($case==='transport'&&$e->getMessage()==='QA transport unavailable','operational503 transport');}
    s26Check(!$svc->verifies&&!$db->nativeWrites(),'pre-auth non401 guard no verify/native write');s26NoPm();
    s26Check(S26ServiceCalls::$verify===$beforeVerify&&S26ServiceCalls::$hash===$beforeHash,'pre-auth guard no actual hash call');
}
$db=new S26Db($md5);$svc=s26Service($db);
for($i=0;$i<5;$i++){
    try{$svc->authenticate($i%2?'qa@example.invalid':'qa','bad','192.0.2.4');throw new RuntimeException('Expected401');}
    catch(ItaHttpError $e){s26Check($e->status===401,'canonical user attempts401');}
}
$beforeVerify=S26ServiceCalls::$verify;$beforeHash=S26ServiceCalls::$hash;
try{$svc->authenticate('qa@example.invalid','bad','192.0.2.4');throw new RuntimeException('Expected429');}
catch(ItaHttpError $e){s26Check($e->status===429,'sixth canonical username/email attempt429');}
s26Check(S26ServiceCalls::$verify===$beforeVerify&&S26ServiceCalls::$hash===$beforeHash&&!$db->nativeWrites(),'429 no padding/native write');s26NoPm();
foreach(['failNative','failAudit'] as $field){
    $db=new S26Db($modern);$db->$field=true;$svc=s26Service($db);$beforeVerify=S26ServiceCalls::$verify;$beforeHash=S26ServiceCalls::$hash;
    try{$svc->authenticate('qa',$pw,'192.0.2.1');throw new LogicException('Expected operational failure');}
    catch(RuntimeException $e){s26Check(str_contains($e->getMessage(),'QA '),'operational error propagates for application503');}
    s26Check(S26ServiceCalls::$verify-$beforeVerify===1&&S26ServiceCalls::$hash===$beforeHash,'post-verification503 no second/padding verify');s26NoPm();
}
$_SESSION=['ita_csrf'=>'QA-csrf'];$_SERVER['REQUEST_METHOD']='POST';$_POST=['csrf_token'=>'wrong'];
$beforeVerify=S26ServiceCalls::$verify;$beforeHash=S26ServiceCalls::$hash;
$app=new ItaApplication();$login=new ReflectionMethod($app,'login');
try{$login->invoke($app);throw new RuntimeException('ExpectedCSRF403');}catch(ItaHttpError $e){s26Check($e->status===403,'actual application CSRF guard stays403');}s26NoPm();
s26Check(S26ServiceCalls::$verify===$beforeVerify&&S26ServiceCalls::$hash===$beforeHash,'CSRF403 zero hash calls');
// Real local session lifecycle after the revised service returns an authenticated actor.
$_SERVER=['HTTPS'=>'on','SERVER_PORT'=>443,'REMOTE_ADDR'=>'127.0.0.1'];$_SESSION=[];
$sessionDirectory=ROOT_PATH.'tmp/s26-session-'.bin2hex(random_bytes(6));
if(!mkdir($sessionDirectory,0700,true))throw new RuntimeException('Cannot create synthetic session folder');
session_save_path($sessionDirectory);
try{
    ItaSession::start(['allow_local_http'=>false]);$oldId=session_id();$oldToken=$_SESSION['ita_csrf'];
    $db=new S26Db($modern);$svc=s26Service($db);$actor=$svc->authenticate('qa@example.invalid',$pw,'192.0.2.5');
    ItaSession::login($actor);$sessionActor=$svc->current();
    s26Check($sessionActor?->id===99&&$sessionActor->revision===3&&$sessionActor->role==='system_manager','service success integrated with real session/current');
    s26Check(session_id()!==$oldId&&$_SESSION['ita_csrf']!==$oldToken,'real login rotates session and CSRF');
    ItaSession::requireCsrf($_SESSION['ita_csrf']);s26Check(true,'session CSRF accepted');
    try{ItaSession::requireCsrf($oldToken);throw new RuntimeException('Expected stale CSRF');}catch(ItaHttpError $e){s26Check($e->status===403,'prelogin CSRF rejected');}
    $nativeCount=count($db->nativeWrites());$_SESSION['ita_seen']=time()-1800;
    s26Check($svc->current()===null&&count($db->nativeWrites())===$nativeCount,'session expiry rejects without another native write');s26NoPm();
}finally{
    if(session_status()===PHP_SESSION_ACTIVE)session_destroy();
    foreach(glob($sessionDirectory.'/sess_*') as $file){if(is_file($file)&&!is_link($file))unlink($file);}
    rmdir($sessionDirectory);
}
s26Check(file_get_contents(ROOT_PATH.'classes/security/pmauth.class.php')===$original,'PmAuth file unchanged during tests');
s26Check(file_get_contents(ROOT_PATH.'classes/services/itaauthservice.class.php')===$serviceOriginal,'Service file unmodified by instrumentation');
echo "PASS: $checks S26 auth checks; service/downstream counters, no timing measurement or DB/host.\n";
