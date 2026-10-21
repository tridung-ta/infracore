<?php
/** Generate exact EXPLAIN statements with synthetic bindings; never connects a database. */
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
class LifecycleExplainDb extends ItaDb {
 public function __construct() {}
 public function fetchAll(string $sql,string $types='',array $params=[]): array { if(!str_contains($sql,'FROM ita_settings'))throw new RuntimeException('Unexpected read');return []; }
}
$out="-- Generated from ItaLifecycleObjects::queryPlans. Read-only EXPLAIN, no execution by generator.\n-- Example date 2026-10-21, store1; manager example scope department1. Change examples to your actual scope/date.\n-- Run in the intended schema in phpMyAdmin. EXPLAIN plans are still UNVERIFIED.\n\n";
foreach (['admin_it','system_manager'] as $role) {
 $actor=new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'username'=>'example','fullname'=>'Example'],$role==='system_manager'?[1]:[]);
 $objects=new ItaLifecycleObjects(new LifecycleExplainDb(),$actor,'2026-10-21');
 foreach ($objects->queryPlans([]) as $name=>$plan) {
  $index=0;$sql=preg_replace_callback('/\?/',function()use(&$index,$plan){$i=$index++;$value=$plan['params'][$i];if($plan['types'][$i]==='i')return (string)(int)$value;return "'".str_replace(["\\","'"],["\\\\","''"],(string)$value)."'";},$plan['sql']);
  if($index!==count($plan['params']))throw new RuntimeException('Binding mismatch');
  $out.="-- $role / $name\nEXPLAIN ".$sql.";\n\n";
 }
}
$file=dirname(__DIR__).'/docs/PHASE08_EXPLAIN.sql';file_put_contents($file,rtrim($out)."\n");echo "Generated 10 exact EXPLAIN queries (2 roles × 5 plans), no DB.\n";
