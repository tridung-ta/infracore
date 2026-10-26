<?php
/** Real auth/session/limiter code with fake DB transport; no config/DB/host. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function securityCheck(bool $ok,string $label): void {global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
function securityReject(callable $fn,int $status): void {try{$fn();throw new RuntimeException('Expected '.$status);}catch(ItaHttpError $e){securityCheck($e->status===$status,'status '.$status);}}
class SecurityPmDb extends PmDb
{
    public array $row=[],$writes=[],$reads=[];
    public function __construct(){}
    public function fetchAll(string $sql,string $types='',array $params=[]): array {$this->reads[]=[$sql,$types,$params];return $this->row?[$this->row]:[];}
    public function execute(string $sql,string $types='',array $params=[]): int {$this->writes[]=[$sql,$types,$params];return 1;}
}
function securityAuth(SecurityPmDb $db): PmAuth {$r=new ReflectionClass(PmAuth::class);$auth=$r->newInstanceWithoutConstructor();(new ReflectionProperty(PmAuth::class,'database'))->setValue($auth,$db);return $auth;}
$pw='QA-only-secret-2026';$db=new SecurityPmDb();$db->row=['id'=>99,'status'=>1,'password'=>md5($pw),'password_hash'=>''];
$auth=securityAuth($db);securityCheck($auth->authenticate(1,'qa',$pw)===99,'MD5 native test credential accepted');
$write=$db->writes[0];securityCheck(str_contains($write[0],'SET password_hash = ?, last_login = NOW()')&&$write[1]==='sii','native migration uses bound columns');
securityCheck(password_verify($pw,$write[2][0])&&in_array(password_get_info($write[2][0])['algoName'],['bcrypt','argon2i','argon2id'],true),'actual PmAuth produces supported modern hash');
securityCheck(!password_verify('incorrect',$write[2][0]),'modern hash rejects wrong password');
securityCheck(!str_contains($write[0],'SET password =')&&!str_contains($write[0],'status='),'login does not rewrite MD5 or status');
$db->row['password_hash']=$write[2][0];$db->writes=[];
securityCheck($auth->authenticate(1,'qa',$pw)===99&&str_contains($db->writes[0][0],'SET last_login = NOW()'),'modern login only updates last_login when rehash not needed');
$db->writes=[];securityCheck($auth->authenticate(1,'qa','bad')===0&&!$db->writes,'incorrect credential has no native write');
$db->row['status']=0;securityCheck($auth->authenticate(1,'qa',$pw)===-1&&!$db->writes,'disabled credential has no native write');
securityCheck($db->reads[0][1]==='iss'&&$db->reads[0][2]===[1,'qa','qa']&&str_contains($db->reads[0][0],'username = ? OR email = ?'),'identity lookup binds store and identifiers');
class SecurityLimitDb extends ItaDb
{
    public int $commits=0,$rollbacks=0,$age=0;
    public array $counts=[],$calls=[];
    public function __construct(){}
    public function beginTransaction(): void{}
    public function commit(): void{$this->commits++;}
    public function rollBack(): void{$this->rollbacks++;}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array {$this->calls[]=[$sql,$types,$params];return ['attempts'=>$this->counts[$params[1]]??0,'age'=>$this->age];}
    public function execute(string $sql,string $types='',array $params=[]): int {
        $this->calls[]=[$sql,$types,$params];
        if(str_contains($sql,'SET attempts=0'))$this->counts[$params[1]]=0;
        if(str_contains($sql,'SET attempts=attempts+1'))$this->counts[$params[1]]=($this->counts[$params[1]]??0)+1;
        return 1;
    }
}
$limits=new SecurityLimitDb();$limiter=new ItaLoginLimiter($limits,1,'synthetic-local-test-key');
for($i=0;$i<5;$i++)$limiter->reserve('user:99','192.0.2.1');
securityCheck($limits->commits===5,'first five attempts reserved');securityReject(fn()=>$limiter->reserve('user:99','192.0.2.1'),429);securityCheck($limits->rollbacks===1,'limit rejection rolls fake transport back');
$limits->age=900;$limiter->reserve('user:99','192.0.2.1');securityCheck(max($limits->counts)===1,'window resets at900 seconds');
$params=json_encode(array_column($limits->calls,2));securityCheck(!str_contains($params,'192.0.2.1')&&!str_contains($params,'user:99'),'limiter binds HMAC buckets, not raw identity/IP');
$limits=new SecurityLimitDb();$limiter=new ItaLoginLimiter($limits,1,'synthetic-local-test-key');
for($i=0;$i<30;$i++)$limiter->reserve('user:'.$i,'192.0.2.2');
securityReject(fn()=>$limiter->reserve('user:next','192.0.2.2'),429);
$_SERVER=['REMOTE_ADDR'=>'192.0.2.1','SERVER_PORT'=>80,'HTTP_X_FORWARDED_PROTO'=>'https'];
securityReject(fn()=>ItaSession::start(['allow_local_http'=>false]),400);
$_SERVER['REMOTE_ADDR']='127.0.0.1';securityReject(fn()=>ItaSession::start(['allow_local_http'=>false]),400);
$_SERVER['HTTPS']='on';$_SERVER['SERVER_PORT']=443;
$temp=dirname(__DIR__).'/tmp/security-session-'.bin2hex(random_bytes(6));
if(!mkdir($temp,0700,true))throw new RuntimeException('Cannot create synthetic session directory');
session_save_path($temp);
try{
 ItaSession::start(['allow_local_http'=>false]);$cookie=session_get_cookie_params();
 securityCheck($cookie['httponly']===true&&$cookie['secure']===true&&$cookie['samesite']==='Lax'&&$cookie['path']==='/','actual HTTPS session cookie configuration');
 securityCheck(ini_get('session.use_strict_mode')==='1'&&ini_get('session.use_only_cookies')==='1','session strict mode/cookies only');
 $user=new ItaUserObjectInfo(['id'=>99,'store_id'=>1,'role'=>'system_manager','auth_revision'=>3,'fullname'=>'QA','username'=>'qa'],[10]);
 $before=session_id();$token=$_SESSION['ita_csrf'];ItaSession::login($user);
 securityCheck(session_id()!==$before&&$_SESSION['ita_csrf']!==$token,'login rotates ID and CSRF');
 securityCheck(ItaSession::valid($user),'fresh session valid');ItaSession::requireCsrf($_SESSION['ita_csrf']);securityCheck(true,'correct CSRF accepted');
 securityReject(fn()=>ItaSession::requireCsrf(null),403);securityReject(fn()=>ItaSession::requireCsrf('bad'),403);
 $_SESSION['ita_seen']=time()-1800;securityCheck(!ItaSession::valid($user),'idle30minutes expires');
 $_SESSION['ita_seen']=time();$_SESSION['ita_started']=time()-28800;securityCheck(!ItaSession::valid($user),'absolute8hours expires');
 $_SESSION['ita_started']=time();$_SESSION['ita_revision']=4;securityCheck(!ItaSession::valid($user),'revision mismatch expires');
 $_SESSION['ita_revision']=3;$_SESSION['ita_store_id']=2;securityCheck(!ItaSession::valid($user),'store mismatch expires');
 ItaSession::logout();securityCheck($_SESSION===[]&&session_status()!==PHP_SESSION_ACTIVE,'logout clears session');
}finally{
 if(session_status()===PHP_SESSION_ACTIVE)session_destroy();
 foreach(glob($temp.'/sess_*') as $file){if(is_file($file)&&!is_link($file))unlink($file);}
 rmdir($temp);
}
echo "PASS: $checks auth/session/limiter checks; real code, fake DB transport and synthetic local session only.\n";
