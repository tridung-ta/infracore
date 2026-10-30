<?php
/** Pure rules and service tests with a scripted adapter. Never connects to MySQL. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks = 0;
function expect(bool $ok, string $label): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($label);
    }
    $checks++;
}
function rejects(callable $callback, int $status): void
{
    try {
        $callback();
    } catch (ItaHttpError $error) {
        expect($error->status === $status,'HTTP status mismatch');
        return;
    }
    throw new RuntimeException('Expected request rejection');
}
function actor(string $role, array $departments = [10]): ItaUserObjectInfo
{
    return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'fullname'=>'QA','username'=>'qa'],$departments);
}
$input = ['asset_code'=>'QA-1','name'=>'Máy tính','category_id'=>'2','department_id'=>'10','purchase_price'=>'1234567890123456.12','purchase_date'=>'2026-10-01','warranty_end'=>'2027-10-01'];
expect(ItaAssetValidation::fields($input)['purchase_price'] === '1234567890123456.12','Decimal price preserved');
foreach ([['name'=>[]],['name'=>''],['asset_code'=>str_repeat('a',65)],['category_id'=>'0'],['department_id'=>'-1'],
    ['purchase_price'=>'-1'],['purchase_price'=>'1e6'],['purchase_price'=>'12345678901234567'],['purchase_price'=>'2.123'],
    ['purchase_date'=>'2026-02-30'],['purchase_date'=>'0001-01-01'],['warranty_end'=>'2026-09-01'],['specs'=>"bad\0value"]] as $bad) {
    rejects(fn()=>ItaAssetValidation::fields(array_replace($input,$bad)),422);
}
ItaAssetValidation::status('broken','maintenance','support_technician');
rejects(fn()=>ItaAssetValidation::status('broken','in_use','admin_it'),422);
rejects(fn()=>ItaAssetValidation::status('retired','in_use','admin_it'),422);
rejects(fn()=>ItaAssetValidation::status('in_use','retired','system_manager'),403);
ItaAssetValidation::status('in_use','retired','admin_it');
expect(ItaAssetService::version(['b'=>2,'a'=>1])===ItaAssetService::version(['a'=>1,'b'=>2]),'Stable version order');
expect(ItaAssetService::version(['a'=>1])!==ItaAssetService::version(['a'=>2]),'Content version detects changes');
ItaAssetValidation::warrantyRange([]);
ItaAssetValidation::warrantyRange(['warranty_from'=>'2026-10-19','warranty_to'=>'2026-10-19']);
rejects(fn()=>ItaAssetValidation::warrantyRange(['warranty_from'=>'2027-01-01','warranty_to'=>'2026-01-01']),422);
rejects(fn()=>ItaAssetValidation::warrantyRange(['warranty_from'=>[]]),422);
rejects(fn()=>ItaAssetValidation::warrantyRange(['warranty_to'=>'2026-02-30']),422);

class Phase03Adapter extends ItaDb
{
    public array $writes = [];
    public bool $committed = false;
    public bool $rolledBack = false;
    public bool $failAudit = false;
    public bool $pendingTicket = false;
    public string $categoryStatus = 'active';
    public function __construct(public array $asset)
    {
    }
    public function beginTransaction(): void {}
    public function commit(): void { $this->committed = true; }
    public function rollBack(): void { $this->rolledBack = true; }
    public function fetchOne(string $sql, string $types = '', array $params = []): ?array
    {
        if (str_contains($sql,'ita_tickets')) return $this->pendingTicket ? ['id'=>9] : null;
        if (str_contains($sql,'ita_assignments')) return null;
        if (str_contains($sql,'ita_asset_categories')) return ['id'=>$params[1],'status'=>$this->categoryStatus];
        if (str_contains($sql,'ita_departments')) return ['id'=>$params[1]];
        if (str_contains($sql,'LAST_INSERT_ID')) return ['id'=>7];
        if (str_contains($sql,'SELECT current_user_id')) return ['current_user_id'=>$this->asset['current_user_id']];
        if (str_contains($sql,'ita_assets')) return $this->asset;
        throw new RuntimeException('Unexpected scripted read');
    }
    public function execute(string $sql, string $types = '', array $params = []): int
    {
        if ($this->failAudit && str_contains($sql,'INSERT INTO ita_audit_logs')) throw new RuntimeException('QA audit failure');
        $this->writes[] = [$sql,$params];
        return 1;
    }
}
$row = ['id'=>7,'category_id'=>2,'department_id'=>10,'status'=>'in_use','current_user_id'=>null,'name'=>'QA'];
$db = new Phase03Adapter($row);
rejects(fn()=>(new ItaAssetService($db,actor('support_technician')))->save(['operation'=>'create']),403);
rejects(fn()=>(new ItaAssetService($db,actor('system_manager')))->save(['operation'=>'delete']),403);
rejects(fn()=>(new ItaAssetService($db,actor('system_manager',[20])))->save(['operation'=>'note','id'=>'7']),403);
expect($db->rolledBack && !$db->writes,'Out-of-scope has no writes');
$db = new Phase03Adapter($row);
$payload = ['operation'=>'note','id'=>'7','version'=>ItaAssetService::version($row),'reason'=>'Ghi chú QA','name'=>'Tampered','store_id'=>2];
(new ItaAssetService($db,actor('support_technician')))->save($payload);
expect($db->committed && count($db->writes)===2,'Note and audit commit together');
expect(!str_contains($db->writes[0][0],'name=') && $db->writes[0][1][2]===1,'Note cannot edit name or forge store');
expect(str_contains($db->writes[1][0],'ita_audit_logs'),'Mutation has audit write');
$db = new Phase03Adapter($row);
rejects(fn()=>(new ItaAssetService($db,actor('admin_it')))->save(array_replace($payload,['version'=>'stale'])),409);
expect($db->rolledBack && !$db->writes,'Stale change rolls back without writes');
$db = new Phase03Adapter($row);$db->failAudit = true;
try {
    (new ItaAssetService($db,actor('support_technician')))->save($payload);
    throw new LogicException('Expected audit exception');
} catch (RuntimeException $error) {
    expect($error->getMessage()==='QA audit failure' && $db->rolledBack && !$db->committed,'Audit error triggers rollback');
}
$db = new Phase03Adapter(array_replace($row,['current_user_id'=>33]));
rejects(fn()=>(new ItaAssetService($db,actor('admin_it')))->save(['operation'=>'delete','id'=>'7','version'=>ItaAssetService::version($db->asset),'reason'=>'QA']),409);
$db = new Phase03Adapter($row);$db->categoryStatus='inactive';
(new ItaAssetService($db,actor('admin_it')))->save($input+['operation'=>'update','id'=>'7','version'=>ItaAssetService::version($row)]);
expect($db->committed,'Unchanged inactive category allowed for existing asset');
$db = new Phase03Adapter($row);$db->categoryStatus='inactive';
rejects(fn()=>(new ItaAssetService($db,actor('admin_it')))->save($input+['operation'=>'create']),422);
rejects(fn()=>(new ItaAssetService($db,actor('admin_it')))->save(array_replace($input,['category_id'=>'3','operation'=>'update','id'=>'7','version'=>ItaAssetService::version($row)])),422);
$db = new Phase03Adapter($row);$db->pendingTicket=true;
foreach (['delete','status'] as $op) {
    rejects(fn()=>(new ItaAssetService($db,actor('admin_it')))->save(['operation'=>$op,'id'=>7,'version'=>ItaAssetService::version($row),'status'=>'retired','reason'=>'QA']),409);
}
expect(!$db->writes,'Pending ticket prevents delete and retirement even inconsistent asset status');
echo $checks." pure/scripted checks passed; no database connection.\n";
