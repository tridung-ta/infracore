<?php
/** Actual shared PmAuth with a controlled race in fake transport. Never opens a DB. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/security/pmauth.class.php';
class ResetRaceDb extends PmDb
{
    public array $row=[],$queries=[];public bool $reset=false,$disable=false,$sameSecond=false;
    public string $replacement='';public int $effectiveWrites=0;
    public function __construct(){}
    public function fetchAll(string $sql,string $types='',array $params=[]): array {return [$this->row];}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array {return $this->row;}
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        if(strlen($types)!==count($params)||substr_count($sql,'?')!==count($params))throw new RuntimeException('Invalid bind contract');
        $this->queries[]=[$sql,$types,$params];
        if($this->reset){$this->row['password_hash']=$this->replacement;$this->row['password']='random-placeholder-fixture';}
        if($this->disable)$this->row['status']=0;
        $guard=str_contains($sql,"BINARY COALESCE(password,'')=BINARY ?")&&str_contains($sql,"BINARY COALESCE(password_hash,'')=BINARY ?")&&str_contains($sql,'status=1');
        if($guard&&((int)$this->row['status']!==1||$this->row['password']!==$params[count($params)-2]||$this->row['password_hash']!==$params[count($params)-1]))return 0;
        if($this->sameSecond)return 0;
        if(str_contains($sql,'SET password_hash = ?'))$this->row['password_hash']=$params[0];
        $this->effectiveWrites++;return 1;
    }
}
$checks=0;function resetRaceCheck(bool $ok,string $label): void {global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
function authRace(ResetRaceDb $db): PmAuth {$auth=(new ReflectionClass(PmAuth::class))->newInstanceWithoutConstructor();(new ReflectionProperty(PmAuth::class,'database'))->setValue($auth,$db);return $auth;}
$old='old-fixture-password';$new='new-fixture-password';$current=password_hash($old,PASSWORD_DEFAULT);$newHash=password_hash($new,PASSWORD_DEFAULT);
foreach(['md5','rehash','modern'] as $kind){
    $db=new ResetRaceDb();$db->row=['id'=>99,'status'=>1,'password'=>md5($old),'password_hash'=>$kind==='md5'?'':($kind==='rehash'?password_hash($old,PASSWORD_BCRYPT,['cost'=>4]):$current)];
    $db->reset=true;$db->replacement=$newHash;
    resetRaceCheck(authRace($db)->authenticate(1,'mock-user',$old)===0,'Old login rejected after reset: '.$kind);
    resetRaceCheck($db->row['password_hash']===$newHash&&password_verify($new,$db->row['password_hash'])&&!password_verify($old,$db->row['password_hash']),'Replacement password survives: '.$kind);
    resetRaceCheck($db->effectiveWrites===0,'No stale native write: '.$kind);
}
$db=new ResetRaceDb();$db->row=['id'=>99,'status'=>1,'password'=>md5($old),'password_hash'=>$current];$db->sameSecond=true;
resetRaceCheck(authRace($db)->authenticate(1,'mock-user',$old)===99,'Same-second last_login zero affected remains a valid login');
$db=new ResetRaceDb();$db->row=['id'=>99,'status'=>1,'password'=>md5($old),'password_hash'=>$current];$db->disable=true;
resetRaceCheck(authRace($db)->authenticate(1,'mock-user',$old)===0&&$db->effectiveWrites===0,'Disable before native write rejects');
$db=new ResetRaceDb();$db->row=['id'=>99,'status'=>1,'password'=>md5($old),'password_hash'=>''];
resetRaceCheck(authRace($db)->authenticate(1,'mock-user',$old)===99&&password_verify($old,$db->row['password_hash']),'Normal MD5 upgrade retained');
resetRaceCheck($db->row['password']===md5($old),'Ordinary login still preserves legacy MD5');
echo "$checks password reset/shared login race checks passed; fake transport only, no DB concurrency proof.\n";
