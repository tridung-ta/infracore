<?php
/** Authenticated Phase 4 HTTP boundary; invoked only after the current actor is loaded. */
class ItaImportController
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    public function run(string $action): void
    {
        $post = in_array($action,['import-upload','import-confirm'],true);
        if ($_SERVER['REQUEST_METHOD']!==($post?'POST':'GET')) {
            throw new ItaHttpError(405,'Phương thức không được hỗ trợ.');
        }
        if ($post) {
            ItaSession::requireCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        }
        if ($action==='asset-export') {
            $rows = (new ItaAssetObjects($this->db,$this->actor))->export($_GET);
            $this->download(ItaExcel::write(ItaExcel::HEADERS,$rows),'ita-assets.xlsx');
            return;
        }
        $service = new ItaImportService($this->db,$this->actor,$_SESSION['ita_csrf']);
        if ($action==='import-template') {
            $this->download(ItaExcel::write(ItaExcel::HEADERS,[]),'ita-assets-template.xlsx');
            return;
        }
        if ($action==='import-errors') {
            $this->download($service->errors(ItaInput::positiveInt($_GET['batch_id'] ?? null)),'ita-import-errors.xlsx');
            return;
        }
        $data = match ($action) {
            'import-upload' => $this->upload($service),
            'import-preview' => $service->preview(ItaInput::positiveInt($_GET['batch_id'] ?? null),ItaInput::positiveInt($_GET['page'] ?? '1')),
            'import-confirm' => $service->confirm($this->body()),
            default => throw new ItaHttpError(404,'Không tìm thấy chức năng.'),
        };
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>true,'data'=>$data,'message'=>''],JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function upload(ItaImportService $service): array
    {
        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? -1)!==UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)
            || !is_string($file['name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
            throw new ItaHttpError(422,'Không nhận được file hợp lệ. Kiểm tra giới hạn upload của máy chủ (cần ít nhất 5 MB).');
        }
        return $service->upload($file['tmp_name'],$file['name']);
    }

    private function body(): array
    {
        if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            throw new ItaHttpError(415,'Yêu cầu cần dữ liệu JSON.');
        }
        $body = file_get_contents('php://input',false,null,0,2049);
        $input = is_string($body) && strlen($body)<=2048 ? json_decode($body,true) : null;
        if (!is_array($input) || array_is_list($input)) {
            throw new ItaHttpError(422,'Dữ liệu xác nhận không hợp lệ.');
        }
        return $input;
    }

    private function download(string $bytes, string $name): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$name.'"');
        header('Content-Length: '.strlen($bytes));
        echo $bytes;
    }
}
