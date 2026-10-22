<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
define('ROOT_PATH',dirname(__DIR__).'/');
foreach (['database/itadb','security/itahttperror','security/itainput','dao/itauserobjectinfo','dao/itauserobjects',
    'services/itaassetvalidation','dao/itaassetobjectinfo','dao/itaassetobjects','services/itaimportservice','services/itaimportcontroller'] as $file) {
    require ROOT_PATH.'classes/'.$file.'.class.php';
}
/** Replace only rendering/storage transport; controller, DAO and service code remain real. */
class ItaExcel
{
    public const HEADERS=['asset_code','name'];
    public static $duringRender;
    public static function write(array $headers,array $rows): string { (self::$duringRender)();return 'PK-synthetic-workbook'; }
}
class ItaImportStorage
{
    public function get(string $name): array { return [['revision'=>1,'line'=>2,'values'=>['asset_code'=>'QA','name'=>'Mock'],'error'=>'Synthetic error']]; }
}
class DownloadDb extends ItaDb
{
    public bool $active=true,$queryFails=false,$available=true,$owner=true;
    public string $role='system_manager';
    public int $revision=1,$department=10;
    public int $identityReads=0;
    public bool $assetChanged=false,$batchChanged=false;
    public function __construct() {}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        if (str_contains($sql,'FROM dc_users')) {
            $this->identityReads++;
            if ($params!==[1,1] || !str_contains($sql,'u.status=1') || !str_contains($sql,'r.enabled=1')) { throw new LogicException('Identity binding'); }
            if ($this->queryFails) { throw new RuntimeException('Synthetic native query failure'); }
            return $this->active?['id'=>1,'username'=>'mock','fullname'=>'Mock','role'=>$this->role,'auth_revision'=>$this->revision]:null;
        }
        if (str_contains($sql,'FROM ita_import_batches')) {
            if ($params!==[1,1,9]) { throw new LogicException('Batch owner binding'); }
            return $this->owner?['id'=>9,'store_id'=>1,'user_id'=>1,'staging_file'=>'mock.json','available'=>(int)$this->available,'status'=>'validated','ok_rows'=>$this->batchChanged?2:1]:null;
        }
        throw new LogicException('Unexpected read');
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        if (str_contains($sql,'FROM ita_user_scopes')) { return [['department_id'=>$this->department]]; }
        if (str_contains($sql,'FROM ita_assets')) {
            if (!str_contains($sql,'a.department_id IN (?)') || $params!==[1,10]) { throw new LogicException('Asset scope binding'); }
            return $this->assetChanged?[]:[['asset_code'=>'QA','name'=>'Mock']];
        }
        throw new LogicException('Unexpected list');
    }
    public function execute(string $sql,string $types='',array $params=[]): int { throw new LogicException('No write permitted'); }
}
$checks=0;$actor=new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>'system_manager','auth_revision'=>1,'fullname'=>'Mock','username'=>'mock'],[10]);
$_SERVER['REQUEST_METHOD']='GET';$_SESSION=['ita_csrf'=>'synthetic-csrf'];
foreach (['asset-export','import-errors'] as $action) {
    $scenarios=['normal','native','role','revision','scope','query',...($action==='asset-export'?['asset']:['expiry','owner','batch'])];
    foreach ($scenarios as $scenario) {
        $db=new DownloadDb();$_GET=$action==='import-errors'?['batch_id'=>'9']:[];
        ItaExcel::$duringRender=static function() use ($db,$scenario): void {
            match($scenario) {
                'native'=>$db->active=false,'role'=>$db->role='support_technician','revision'=>$db->revision=2,
                'scope'=>$db->department=11,'query'=>$db->queryFails=true,'asset'=>$db->assetChanged=true,
                'expiry'=>$db->available=false,'owner'=>$db->owner=false,'batch'=>$db->batchChanged=true,default=>null
            };
        };
        ob_start();$status=null;
        try { (new ItaImportController($db,$actor))->run($action); }
        catch (ItaHttpError $e) { $status=$e->status; }
        catch (RuntimeException $e) { if ($scenario!=='query') { throw $e; }$status=503; }
        $bytes=ob_get_clean();
        $expected=match($scenario) {'normal'=>null,'asset','batch'=>409,'expiry'=>410,'owner'=>404,'query'=>503,default=>403};
        if ($status!==$expected) { throw new RuntimeException("Status $action/$scenario"); }$checks++;
        if ($bytes!==($scenario==='normal'?'PK-synthetic-workbook':'')) { throw new RuntimeException("Bytes $action/$scenario"); }$checks++;
        if ($db->identityReads!==1) { throw new RuntimeException('Expected actual post-render identity recheck'); }$checks++;
    }
}
echo "$checks scripted controller export checks passed; synthetic renderer/storage, no DB or real XLSX/HTTP headers.\n";
