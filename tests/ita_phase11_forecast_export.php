<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
require __DIR__.'/fixtures/ita_forecast_db.php';
class ExportRecheckDb extends ForecastTestDb
{
    public bool $native=true,$nativeFails=false;
    public int $audits=0;
    public array $scopes=[10];
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        if (str_contains($sql,'FROM ita_user_scopes')) { return array_map(fn($id)=>['department_id'=>$id],$this->scopes); }
        return parent::fetchAll($sql,$types,$params);
    }
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        if (str_contains($sql,'FROM dc_users')) {
            if ($types!=='ii' || $params!==[1,1] || !str_contains($sql,'status=1')) { throw new LogicException('Native binding'); }
            if ($this->nativeFails) { throw new RuntimeException('Synthetic native query failure'); }
            return $this->native?['id'=>1]:null;
        }
        return parent::fetchOne($sql,$types,$params);
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        $result=parent::execute($sql,$types,$params);
        if (str_contains($sql,'ita_audit_logs')) { $this->audits++; }
        return $result;
    }
}
$actor=new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>'admin_it','auth_revision'=>1,'fullname'=>'Mock','username'=>'mock']);
$checks=0;
foreach (['normal','disabled','missing','store','query','audit','role'] as $scenario) {
    $db=new ExportRecheckDb();$service=new ItaForecastService($db,$actor);
    // Simulate native/role changes during the render, then run the actual post-render boundary.
    $render=static function() use ($db,$scenario): string {
        $db->native=!in_array($scenario,['disabled','missing','store'],true);$db->nativeFails=$scenario==='query';
        $db->auditFails=$scenario==='audit';if ($scenario==='role') { $db->role='system_manager'; }
        return 'synthetic-file-bytes';
    };
    ob_start();$failed=false;
    try { $bytes=$render();$service->auditExport(['rows'=>[],'year'=>2026,'summary'=>[]]);echo $bytes; }
    catch (Throwable $e) { $failed=true;if ($scenario==='normal') { throw $e; } }
    $output=ob_get_clean();
    if (($scenario==='normal')!==(!$failed) || $output!==($scenario==='normal'?'synthetic-file-bytes':'')) { throw new RuntimeException('Bytes '.$scenario); }$checks++;
    if ($db->audits!==($scenario==='normal'?1:0)) { throw new RuntimeException('Successful audit '.$scenario); }$checks++;
    if ($scenario!=='normal' && !$db->rolledBack) { throw new RuntimeException('Rollback '.$scenario); }$checks++;
}
$manager=new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>'system_manager','auth_revision'=>1,'fullname'=>'Mock','username'=>'mock'],[10]);
foreach (['normal','scope-revoked','outside-department'] as $scenario) {
    $db=new ExportRecheckDb();$db->role='system_manager';$service=new ItaForecastService($db,$manager);
    // Permission changes take place after synthetic rendering, before the real export audit boundary.
    $bytes='synthetic-manager-bytes';
    if ($scenario==='scope-revoked') { $db->scopes=[]; }
    ob_start();$status=null;
    try { $service->auditExport(['rows'=>[['department_id'=>$scenario==='outside-department'?11:10]],'year'=>2026,'summary'=>[]]);echo $bytes; }
    catch (ItaHttpError $e) { $status=$e->status; }
    $output=ob_get_clean();
    if ($status!==($scenario==='normal'?null:403)) { throw new RuntimeException('Manager status '.$scenario); }$checks++;
    if ($output!==($scenario==='normal'?$bytes:'')) { throw new RuntimeException('Manager bytes '.$scenario); }$checks++;
    if ($db->audits!==($scenario==='normal'?1:0)) { throw new RuntimeException('Manager audit '.$scenario); }$checks++;
}
echo "$checks scripted forecast export checks passed; no DB, synthetic render, not HTTP/DB integration.\n";
