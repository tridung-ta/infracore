<?php
/** Generator only. It never connects to or executes SQL against any DB. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
class Explain10Db extends ItaDb
{
    public function __construct() {}
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        if(!str_contains($sql,'FROM ita_settings'))throw new RuntimeException('Generator cannot execute this query');return [];
    }
}
$out="-- Exact source read plans, synthetic store 1 / manager scope 10. Generator does NOT execute SQL.\n-- User-run EXPLAIN on the intended DB only; indexes/performance UNVERIFIED. No DDL.\n-- Group counts are computed in PHP from bounded scoped rows (maximum 5000, overflow refused).\n\n";
foreach(['admin_it','system_manager'] as $role){
 $actor=new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'fullname'=>'Mock','username'=>'mock'],$role==='admin_it'?[]:[10]);$db=new Explain10Db();
 $dao=new ItaDashboardObjects($db,$actor,'2026-10-21T10:00:00+07:00');$plans=$dao->queryPlans([]);$plans['licenses']=$dao->licensePlan([]);$plans['lifecycle']=(new ItaLifecycleObjects($db,$actor,'2026-10-21'))->queryPlans([])['report'];
 foreach($plans as $name=>$p){$i=0;$sql=preg_replace_callback('/\?/',function() use (&$i,$p){$v=$p['params'][$i];$type=$p['types'][$i++];return $type==='i'?(string)(int)$v:"'".str_replace("'","''",(string)$v)."'";},$p['sql']);if($i!==count($p['params']))throw new RuntimeException('Bindings');$out.="-- $role / $name\nEXPLAIN $sql;\n\n";}
}
$out.="-- Budget reuses PHASE09_EXPLAIN.sql; actor/scopes use existing primary-key reads.\n";
file_put_contents(dirname(__DIR__).'/docs/PHASE10_EXPLAIN.sql',$out);
echo "Generated 6 exact EXPLAIN statements; no DB.\n";
