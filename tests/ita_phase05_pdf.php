<?php
/** Real renderer + scripted read/audit adapter. No configuration or database connection. */
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function pdfCheck(bool $ok, string $label): void
{
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
function pdfReject(callable $callback, int $status): void
{
    try { $callback(); }
    catch (ItaHttpError $error) { pdfCheck($error->status===$status,'Expected '.$status); return; }
    throw new RuntimeException('Expected rejection');
}
function pdfActor(string $role='admin_it', array $scope=[10]): ItaUserObjectInfo
{
    return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'fullname'=>'QA','username'=>'qa'],$scope);
}
class PdfAdapter extends ItaDb
{
    public array $assignment=['id'=>9,'store_id'=>1,'asset_id'=>7,'assigned_by'=>111,'to_user_id'=>222,'to_department_id'=>null,
        'handover_no'=>'QA-BG-20261020','assigned_at'=>'2026-10-20 03:15:00','due_return'=>'2026-12-20','returned_at'=>'2026-10-20 04:20:00',
        'acknowledged_at'=>'2026-10-20 03:30:00','acknowledged_by'=>222,'note'=>'Đã kiểm tra bàn phím, màn hình và phụ kiện đầy đủ.'];
    public array $asset=['id'=>7,'store_id'=>1,'department_id'=>10,'asset_code'=>'QA-LT-0001','name'=>'Máy tính xách tay kiểm thử tiếng Việt','serial_no'=>'0000123456','status'=>'in_use','is_deleted'=>0];
    public array $audits=[];
    public bool $missing=false;
    public bool $auditFailure=false;
    public bool $revokeDuringRender=false;
    public bool $changeDuringRender=false;
    public string $role='admin_it';
    private int $actorReads=0;
    public function __construct() {}
    public function fetchOne(string $sql, string $types='', array $params=[]): ?array
    {
        pdfCheck(strlen($types)===count($params),'Prepared reads match');
        if (str_contains($sql,'JOIN ita_user_roles')) {
            $this->actorReads++;
            if ($this->changeDuringRender && $this->actorReads>1) $this->asset['name']='Đã thay đổi lúc xuất PDF';
            return $this->revokeDuringRender && $this->actorReads>1 ? null : ['id'=>111,'username'=>'qa','fullname'=>'QA','role'=>$this->role,'auth_revision'=>1];
        }
        if (str_contains($sql,'ita_assignments')) return !$this->missing && $params===[1,9] ? $this->assignment : null;
        if (str_contains($sql,'ita_assets')) return $params===[1,7] && !$this->asset['is_deleted'] ? $this->asset : null;
        if (str_contains($sql,'ita_audit_logs')) return ['after_json'=>'{"return_note":"Đã nhận lại thiết bị, tình trạng tốt."}'];
        if (str_contains($sql,'dc_users')) return ['username'=>'qa','fullname'=>$params[1]===111?'Nguyễn Văn Giao':'Trần Thị Nhận'];
        if (str_contains($sql,'ita_departments')) return ['name'=>'Phòng Công nghệ thông tin'];
        throw new RuntimeException('Unexpected read');
    }
    public function fetchAll(string $sql, string $types='', array $params=[]): array
    {
        if (str_contains($sql,'ita_user_scopes')) return [['department_id'=>10]];
        throw new RuntimeException('Unexpected list');
    }
    public function execute(string $sql, string $types='', array $params=[]): int
    {
        pdfCheck(strlen($types)===count($params),'Prepared audit parameters');
        if (!str_contains($sql,'INSERT INTO ita_audit_logs')) throw new RuntimeException('Unexpected write');
        if ($this->auditFailure) throw new RuntimeException('QA audit unavailable');
        $this->audits[]=$params;
        return 1;
    }
}
$output=ROOT_PATH.'tmp/pdfs';
if (in_array('--unavailable',$argv,true)) {
    pdfCheck(!ItaHandoverPdf::available(),'GD is not loaded in this process');
    pdfReject(fn()=>ItaHandoverPdf::render([]),503);
    echo "$checks missing-runtime checks passed; no PDF or database.\n";
    exit;
}
if (!is_dir($output)) mkdir($output,0700,true);
$db=new PdfAdapter();$service=new ItaHandoverService($db,pdfActor());
$document=$service->document(9);
pdfCheck($document['number']==='QA-BG-20261020','Receipt number from persisted row');
pdfCheck(in_array('20/10/2026 10:15',array_column($document['fields'],'value'),true),'UTC converted to Vietnam time');
pdfCheck(str_contains(json_encode($document,JSON_UNESCAPED_UNICODE),'Trần Thị Nhận'),'Vietnamese recipient');
$compile=$output.'/smarty';if (!is_dir($compile)) mkdir($compile,0700);
$injection=$document;$injection['note']='<img src="file:///private"><script>alert(1)</script>';
$html=ItaHandoverPdf::html($injection,$compile);
pdfCheck(!str_contains($html,'<img') && !str_contains($html,'<script>') && str_contains($html,'&lt;img'),'Untrusted markup escaped');
pdfReject(fn()=>(new ItaHandoverService($db,pdfActor('system_manager',[20])))->document(9),403);
pdfReject(fn()=>$service->document(10),404);
$db->asset['is_deleted']=1;pdfReject(fn()=>$service->document(9),404);$db->asset['is_deleted']=0;
$db->assignment['to_user_id']=null;$db->assignment['to_department_id']=10;
pdfCheck(str_contains(json_encode($service->document(9),JSON_UNESCAPED_UNICODE),'Phòng Công nghệ thông tin'),'Department receipt supported');
$db->assignment['to_user_id']=222;$db->assignment['to_department_id']=null;
if (!ItaHandoverPdf::available()) { echo "$checks checks passed; GD/vendor missing, actual PDF not run. Use php -d extension=gd.\n"; exit(2); }
$before=glob(sys_get_temp_dir().'/infracore-pdf-*');
$bytes=$service->download(9);
pdfCheck(str_starts_with($bytes,'%PDF-') && strlen($bytes)>10000,'Actual PDF bytes');
file_put_contents($output.'/handover-short.pdf',$bytes);
pdfCheck(count($db->audits)===1 && $db->audits[0][2]==='assignment.pdf_download','Download audited once');
pdfCheck(!str_contains($db->audits[0][6],'Trần'),'Audit excludes recipient name');
pdfCheck(array_diff(glob(sys_get_temp_dir().'/infracore-pdf-*'),$before)===[],'Per-call temp cleaned');
$long=$document;$long['note']=str_repeat('Tình trạng thiết bị tốt, các cổng kết nối hoạt động ổn định. ',60);
file_put_contents($output.'/handover-long.pdf',ItaHandoverPdf::render($long));
foreach (['system_manager','support_technician'] as $role) {
    $adapter=new PdfAdapter();$adapter->role=$role;
    pdfCheck(str_starts_with((new ItaHandoverService($adapter,pdfActor($role)))->download(9),'%PDF-'),'Scoped role download '.$role);
}
$adapter=new PdfAdapter();$adapter->revokeDuringRender=true;
pdfReject(fn()=>(new ItaHandoverService($adapter,pdfActor()))->download(9),403);
pdfCheck(!$adapter->audits,'Permission revoked during render returns no download/audit');
$adapter=new PdfAdapter();$adapter->changeDuringRender=true;
pdfReject(fn()=>(new ItaHandoverService($adapter,pdfActor()))->download(9),409);
pdfCheck(!$adapter->audits,'Changed source prevents stale PDF download');
$adapter=new PdfAdapter();$adapter->auditFailure=true;
try { (new ItaHandoverService($adapter,pdfActor()))->download(9); throw new LogicException('Expected audit failure'); }
catch (RuntimeException $error) { pdfCheck($error->getMessage()==='QA audit unavailable','Audit failure blocks download'); }
echo "$checks checks passed: real PDF/Smarty + scripted permissions/audit. No database.\n";
