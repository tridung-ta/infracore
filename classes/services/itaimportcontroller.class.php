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
            $bytes = ItaExcel::write(ItaExcel::HEADERS,$rows);
            $current = $this->currentActor();
            $latest = (new ItaAssetObjects($this->db,$current))->export($_GET);
            if ($rows!==$latest) {
                throw new ItaHttpError(409,'Dữ liệu hoặc phạm vi tài sản đã đổi trong lúc tạo file. Hãy xuất lại.');
            }
            $this->download($bytes,'ita-assets.xlsx');
            return;
        }
        $service = new ItaImportService($this->db,$this->actor,$_SESSION['ita_csrf']);
        if ($action==='import-template') {
            $this->download(ItaExcel::write(ItaExcel::HEADERS,[]),'ita-assets-template.xlsx');
            return;
        }
        if ($action==='import-errors') {
            $id = ItaInput::positiveInt($_GET['batch_id'] ?? null);
            $version = $service->errorsVersion($id);
            $bytes = $service->errors($id);
            $current = $this->currentActor();
            $latest = new ItaImportService($this->db,$current,$_SESSION['ita_csrf']);
            if (!hash_equals($version,$latest->errorsVersion($id))) {
                throw new ItaHttpError(409,'Lô import đã đổi trong lúc tạo file. Hãy tải lại.');
            }
            $this->download($bytes,'ita-import-errors.xlsx');
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

    /** Recheck native identity and effective ITA permissions after the slow renderer. */
    private function currentActor(): ItaUserObjectInfo
    {
        $current = (new ItaUserObjects($this->db))->current($this->actor->storeId,$this->actor->id);
        if (!$current || $current->role!==$this->actor->role || $current->revision!==$this->actor->revision) {
            throw new ItaHttpError(403,'Tài khoản hoặc quyền đã thay đổi. Không thể tải file.');
        }
        $known = $this->actor->departmentIds;
        $scopes = $current->departmentIds;
        sort($known);sort($scopes);
        if ($known!==$scopes) {
            throw new ItaHttpError(403,'Phạm vi đã thay đổi. Hãy tải lại trước khi xuất file.');
        }
        return $current;
    }

    private function download(string $bytes, string $name): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$name.'"');
        header('Content-Length: '.strlen($bytes));
        echo $bytes;
    }
}
