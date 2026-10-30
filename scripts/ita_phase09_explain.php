<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
require dirname(__DIR__).'/tests/fixtures/ita_forecast_db.php';
$out="-- Exact read-only source queries from ItaForecastObjects::queryPlans; generator never executes SQL.\n-- Synthetic store1, manager department10; replace with actual scope. Dates generated ".date('Y-m-d').".\n-- EXPLAIN execution and indexes are UNVERIFIED. No CREATE/ALTER/INSERT/UPDATE.\n\n";
foreach (['admin_it','system_manager'] as $role) {
    $actor=new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'username'=>'mock','fullname'=>'Mock'],[10]);
    $plans=(new ItaForecastObjects(new ForecastTestDb(),$actor))->queryPlans([]);
    foreach (['assets','history'] as $name) {
        $plan=$plans[$name];$index=0;
        $sql=preg_replace_callback('/\?/',function()use(&$index,$plan){$i=$index++;$v=$plan['params'][$i];return $plan['types'][$i]==='i'?(string)(int)$v:"'".str_replace(["\\","'"],["\\\\","''"],(string)$v)."'";},$plan['sql']);
        if ($index!==count($plan['params'])) { throw new RuntimeException('Binding mismatch'); }
        $out.="-- $role / $name\nEXPLAIN $sql;\n\n";
    }
}
file_put_contents(dirname(__DIR__).'/docs/PHASE09_EXPLAIN.sql',rtrim($out)."\n");
echo "Generated 4 exact EXPLAIN queries, not executed.\n";
