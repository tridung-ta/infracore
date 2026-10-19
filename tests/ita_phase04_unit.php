<?php
/** Actual XLSX + scripted adapter tests. Does not load configuration or connect to a database. */
if (PHP_SAPI!=='cli') {
    http_response_code(404);
    exit;
}
error_reporting(E_ALL & ~E_DEPRECATED);
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks = 0;
function check04(bool $value, string $label): void
{
    global $checks;
    if (!$value) {
        throw new RuntimeException($label);
    }
    $checks++;
}
function reject04(callable $callback, int $status): void
{
    try {
        $callback();
    } catch (ItaHttpError $error) {
        check04($error->status===$status,'Expected HTTP '.$status.', got '.$error->status);
        return;
    }
    throw new RuntimeException('Expected rejection '.$status);
}
function actor04(string $role = 'admin_it', int $revision = 1): ItaUserObjectInfo
{
    return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>$revision,'fullname'=>'QA','username'=>'qa'],[10]);
}
$values = array_fill_keys(ItaExcel::HEADERS,'');
$values = array_replace($values,['asset_code'=>'QA-001','category_code'=>'PC','name'=>'Thiết bị tiếng Việt','serial_no'=>'0000123','purchase_date'=>'2026-10-19','department_code'=>'IT','status'=>'in_use','purchase_price'=>'1234567890123456.12']);
$path = tempnam(sys_get_temp_dir(),'ita-phase04-test-');
$files = [$path];
function xlsx04(string $path, array $values): void
{
    file_put_contents($path,ItaExcel::write(ItaExcel::HEADERS,[array_values($values)]));
}
function xml04(string $path, callable $change): void
{
    $zip = new ZipArchive();
    $zip->open($path);
    $name = 'xl/worksheets/sheet1.xml';
    $zip->addFromString($name,$change($zip->getFromName($name)));
    $zip->close();
}
class Adapter04 extends ItaDb
{
    public array $batch = [];
    public array $writes = [];
    public array $queries = [];
    public bool $committed = false;
    public bool $rolledBack = false;
    public bool $duplicate = false;
    public bool $failAudit = false;
    public bool $categoryActive = true;
    public bool $actorEnabled = true;
    public int $referenceId = 10;
    public array $exportRows = [];
    public function __construct() {}
    public function beginTransaction(): void { $this->committed = false; }
    public function commit(): void { $this->committed = true; }
    public function rollBack(): void { $this->rolledBack = true; }
    public function fetchOne(string $sql, string $types = '', array $params = []): ?array
    {
        $this->queries[] = [$sql,$params];
        if (str_contains($sql,'LAST_INSERT_ID')) return ['id'=>9];
        if (str_contains($sql,'ita_import_batches')) return $this->batch && $params[0]===$this->batch['store_id'] && $params[1]===$this->batch['user_id'] ? $this->batch : null;
        if (str_contains($sql,'ita_user_roles')) return ['role'=>'admin_it','enabled'=>(int) $this->actorEnabled,'auth_revision'=>1];
        if (str_contains($sql,'ita_asset_categories')) return $this->categoryActive ? ['id'=>2] : null;
        if (str_contains($sql,'ita_departments')) return ['id'=>$this->referenceId];
        throw new RuntimeException('Unexpected read: '.$sql);
    }
    public function fetchAll(string $sql, string $types = '', array $params = []): array
    {
        $this->queries[] = [$sql,$params];
        check04(strlen($types)===count($params),'Prepared read parameter count');
        if (str_contains($sql,'CONVERT(? USING utf8mb4)')) return array_map(fn($value)=>['weight_key'=>strtolower(rtrim($value))],$params);
        if (str_contains($sql,'weight_key')) return $this->duplicate ? [['weight_key'=>'qa-001']] : [];
        if (str_contains($sql,'LIMIT 5001')) return $this->exportRows;
        throw new RuntimeException('Unexpected read: '.$sql);
    }
    public function execute(string $sql, string $types = '', array $params = []): int
    {
        check04(strlen($types)===count($params),'Prepared write parameter count');
        if ($this->failAudit && str_contains($sql,'ita_audit_logs')) throw new RuntimeException('QA audit failure');
        $this->writes[] = [$sql,$params];
        if (str_contains($sql,'INSERT INTO ita_import_batches')) {
            $this->batch = ['id'=>9,'store_id'=>$params[0],'user_id'=>$params[1],'filename'=>$params[2],'staging_file'=>$params[3],
                'total_rows'=>$params[4],'ok_rows'=>$params[5],'error_rows'=>$params[6],'status'=>'validated','available'=>1,'expires_at'=>'2099-01-01 00:00:00'];
        }
        if (str_contains($sql,"status='completed'")) $this->batch['status'] = 'completed';
        return 1;
    }
}
try {
    xlsx04($path,$values);
    $rows = ItaExcel::read($path,'qa.xlsx');
    check04($rows[0]['values']===$values,'Real XLSX roundtrip preserves Vietnamese, leading zeros and decimal precision');
    $injection = array_replace($values,['name'=>'=HYPERLINK("https://invalid")']);
    xlsx04($path,$injection);
    check04(ItaExcel::read($path,'qa.xlsx')[0]['values']['name']===$injection['name'],'Formula-like export remains plain text');
    $zip = new ZipArchive();
    $zip->open($path);
    check04(!str_contains($zip->getFromName('xl/worksheets/sheet1.xml'),'<f>'),'Writer does not emit formula nodes');
    $zip->close();
    reject04(fn()=>ItaExcel::read($path,'qa.csv'),422);
    xml04($path,fn($xml)=>str_replace('</sheetData>','<row r="3"><c r="A3"><f>1+1</f><v>2</v></c></row></sheetData>',$xml));
    reject04(fn()=>ItaExcel::read($path,'qa.xlsx'),422);
    xlsx04($path,$values);
    $zip->open($path);
    $zip->addFromString('xl/_rels/qa.rels','<Relationships><Relationship TargetMode="&#69;xternal" Target="file:///invalid"/></Relationships>');
    $zip->close();
    reject04(fn()=>ItaExcel::read($path,'qa.xlsx'),422);
    xlsx04($path,$values);
    $zip->open($path);
    $zip->addFromString('xl/qa.xml',str_repeat('a',2*1024*1024));
    $zip->close();
    reject04(fn()=>ItaExcel::read($path,'qa.xlsx'),422);
    xlsx04($path,$values);
    xml04($path,fn($xml)=>str_replace('<worksheet','<!DOCTYPE a [<!ENTITY x SYSTEM "file:///invalid">]><worksheet',$xml));
    reject04(fn()=>ItaExcel::read($path,'qa.xlsx'),422);
    xlsx04($path,$values);
    $zip->open($path);
    $zip->addFromString('xl/externalLinks/test.xml','<test/>');
    $zip->close();
    reject04(fn()=>ItaExcel::read($path,'qa.xlsx'),422);
    file_put_contents($path,ItaExcel::write(['name'],[['QA']]));
    reject04(fn()=>ItaExcel::read($path,'qa.xlsx'),422);
    file_put_contents($path,ItaExcel::write(ItaExcel::HEADERS,[]));
    reject04(fn()=>ItaExcel::read($path,'qa.xlsx'),422);
    xlsx04($path,$values);
    xml04($path,fn($xml)=>str_replace('</sheetData>','<row r="5002"><c r="A5002" t="inlineStr"><is><t>QA</t></is></c></row></sheetData>',$xml));
    reject04(fn()=>ItaExcel::read($path,'qa.xlsx'),422);
    $db = new Adapter04();
    $validator = new ItaImportRows($db,actor04());
    check04($validator->validate($rows)[0]['error']==='','Valid source row');
    foreach ([['purchase_date'=>'2026-02-30'],['purchase_price'=>'-1'],['serial_no'=>''],['status'=>'invalid'],['status'=>'retired']] as $bad) {
        check04($validator->validate([['line'=>2,'values'=>array_replace($values,$bad)]])[0]['error']!=='','Invalid date/price/required/status rejected');
    }
    check04($validator->validate([$rows[0],['line'=>3,'values'=>array_replace($values,['asset_code'=>'qa-001'])]])[0]['error']!=='','Duplicate weight keys rejected (adapter simulates case collation)');
    $db->duplicate = true;
    check04($validator->validate($rows)[0]['error']!=='','Existing duplicate rejected');
    $db->duplicate = false;
    $db->referenceId = 20;
    check04((new ItaImportRows($db,actor04('system_manager')))->validate($rows)[0]['error']!=='','Manager scope enforced');
    $db->referenceId = 10;
    $retired = [['line'=>2,'values'=>array_replace($values,['status'=>'retired','status_reason'=>'QA'])]];
    check04((new ItaImportRows($db,actor04('system_manager')))->validate($retired)[0]['error']!=='','Manager cannot retire through import');
    reject04(fn()=>new ItaImportService($db,actor04('support_technician'),'qa'),403);
    $_SESSION = ['ita_csrf'=>'qa-session'];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    reject04(fn()=>(new ItaImportController($db,actor04()))->run('import-confirm'),403);
    $_SERVER['HTTP_X_CSRF_TOKEN'] = 'wrong';
    reject04(fn()=>(new ItaImportController($db,actor04()))->run('import-upload'),403);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    reject04(fn()=>(new ItaImportController($db,actor04()))->run('import-confirm'),405);
    reject04(fn()=>(new ItaImportController($db,actor04('support_technician')))->run('import-template'),403);
    reject04(fn()=>(new ItaImportStorage())->get('../bad.json'),410);
    reject04(fn()=>(new ItaImportStorage())->put([['values'=>['specs'=>str_repeat('a',32*1024*1024)]]]),422);
    $memoryLimit = ini_get('memory_limit');
    try {
        ini_set('memory_limit','64M');
        reject04(fn()=>ItaExcel::write(ItaExcel::HEADERS,array_fill(0,5000,array_values($values))),422);
    } finally {
        ini_set('memory_limit',$memoryLimit);
    }
    xlsx04($path,$values);
    $service = new ItaImportService($db,actor04(),'qa-session');
    $preview = $service->upload($path,'qa.xlsx');
    check04($preview['ok_rows']===1 && $preview['error_rows']===0,'Upload returns actual validated preview');
    check04(!array_filter($db->writes,fn($write)=>str_contains($write[0],'INSERT INTO ita_assets')),'Preview does not write assets');
    $input = ['batch_id'=>9,'confirm_token'=>$preview['confirm_token']];
    reject04(fn()=>$service->confirm(array_replace($input,['confirm_token'=>'bad'])),409);
    $db->batch['available'] = 0;
    reject04(fn()=>$service->confirm($input),410);
    $db->batch['available'] = 1;
    $db->batch['user_id'] = 222;
    reject04(fn()=>$service->preview(9,1),404);
    $db->batch['user_id'] = 111;
    reject04(fn()=>(new ItaImportService($db,actor04('admin_it',2),'qa-session'))->preview(9,1),409);
    $db->actorEnabled = false;
    reject04(fn()=>$service->confirm($input),403);
    $db->actorEnabled = true;
    $db->duplicate = true;
    reject04(fn()=>$service->confirm($input),409);
    $db->duplicate = false;
    $db->categoryActive = false;
    reject04(fn()=>$service->confirm($input),409);
    $db->categoryActive = true;
    $db->failAudit = true;
    try { $service->confirm($input); throw new LogicException('Expected failure'); }
    catch (RuntimeException $error) { check04($db->rolledBack && !$db->committed,'Audit failure invokes rollback (scripted, not DB integration)'); }
    $db->failAudit = false;
    $result = $service->confirm($input);
    check04($result['imported_rows']===1 && $db->committed,'Confirm inserts rows/audits and commits adapter');
    $writes = count($db->writes);
    check04($service->confirm($input)['already_completed'] && count($db->writes)===$writes,'Repeat confirm does not insert again');
    $db->batch['staging_file'] = 'missing.json';
    check04($service->preview(9,1)['status']==='completed','Completed status remains readable after staging is unavailable');
    $export = new Adapter04();
    $export->exportRows = [$values+['id'=>1,'department_id'=>10]];
    $output = (new ItaAssetObjects($export,actor04('system_manager')))->export(['status'=>'broken','q'=>'QA%','department_id'=>'10','category_id'=>'2','purchase_year'=>'2026','warranty_from'=>'2026-10-01']);
    $query = $export->queries[0];
    check04(str_contains($query[0],'a.department_id IN (?)') && str_contains($query[0],'LIMIT 5001') && in_array('%QA=%%',$query[1],true),'Export uses server scope, escaped filters and row bound');
    check04($output[0][3]==='0000123','Export fields mapped to template');
    reject04(fn()=>(new ItaAssetObjects($export,actor04('support_technician')))->export([]),403);
    $export->exportRows = array_fill(0,5001,[]);
    reject04(fn()=>(new ItaAssetObjects($export,actor04()))->export([]),422);
    echo "$checks checks passed: actual XLSX + pure rules + scripted adapter. No database created or connected.\n";
} finally {
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
}
