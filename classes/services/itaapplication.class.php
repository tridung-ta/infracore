<?php
class ItaApplication
{
    private array $config;
    private ?ItaDb $db = null;
    private ?ItaUserObjectInfo $user = null;
    private string $action = '';

    public function run(): void
    {
        try {
            $this->action = ItaInput::text($_GET['act'] ?? '', 30);
            if ($this->action==='license-key') {
                header('Cache-Control: no-store, private');
            }
            $this->config = ItaDb::configuration();
            ItaSession::start($this->config);
            if ($this->action === 'login') {
                $this->login();
                return;
            }
            if (empty($_SESSION['ita_user_id'])) {
                $this->unauthenticated();
                return;
            }
            $this->db = ItaDb::connect($this->config);
            $this->user = (new ItaAuthService($this->db, $this->config))->current();
            if (!$this->user) {
                $this->unauthenticated();
                return;
            }
            $this->dispatch();
        } catch (ItaHttpError $error) {
            $this->error($error->status, $error->getMessage());
        } catch (Throwable $error) {
            $this->error(503, 'Hệ thống chưa sẵn sàng. Vui lòng kiểm tra cấu hình hoặc thử lại sau.');
        }
    }

    private function login(): void
    {
        if (!empty($_SESSION['ita_user_id']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            header('Location: /admin.php?op=ita', true, 303);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ItaSession::requireCsrf($_POST['csrf_token'] ?? null);
            $identity = ItaInput::text($_POST['username'] ?? '', 200);
            $password = $_POST['password'] ?? null;
            if ($identity === '' || !is_string($password) || $password === '' || strlen($password)>1024 || str_contains($password,"\0")) {
                throw new ItaHttpError(422, 'Vui lòng nhập thông tin đăng nhập hợp lệ.');
            }
            $this->db = ItaDb::connect($this->config);
            $user = (new ItaAuthService($this->db, $this->config))->authenticate($identity, $password, $_SERVER['REMOTE_ADDR'] ?? '');
            ItaSession::login($user);
            header('Location: /admin.php?op=ita', true, 303);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw new ItaHttpError(405, 'Phương thức không được hỗ trợ.');
        }
        $this->render('admin/ita/login.tpl.html', ['itaCsrf'=>$_SESSION['ita_csrf'],
            'itaError'=>$this->config['ready'] ? '' : 'Chưa cấu hình kết nối ITA. Quản trị viên cần hoàn tất cấu hình trước khi đăng nhập.']);
    }

    private function dispatch(): void
    {
        if ($this->action === '') {
            if (!in_array($_SERVER['REQUEST_METHOD'], ['GET','HEAD'], true)) {
                throw new ItaHttpError(405, 'Phương thức không được hỗ trợ.');
            }
            $this->render('admin/ita/layout.tpl.html', ['itaMode'=>'live','itaCsrf'=>$_SESSION['ita_csrf'],
                'itaUserName'=>$this->user->name,'itaRoleLabel'=>$this->user->roleLabel()]);
            return;
        }
        if ($this->action === 'logout') {
            $this->requirePost();
            try {
                (new ItaAuditService($this->db,$this->user))->record('auth.logout','user',$this->user->id);
            } finally {
                ItaSession::logout();
            }
            header('Location: /admin.php?op=ita&act=login', true, 303);
            return;
        }
        $this->api();
    }

    private function api(): void
    {
        if (in_array($this->action,['dashboard','dashboard-charts','alerts','report-preview','report-export'],true)) {
            $this->reportApi();return;
        }
        if (str_starts_with($this->action,'forecast')) { header('Cache-Control: no-store, private'); }
        if (in_array($this->action,['forecast-config-save','forecast-plan-save','forecast-export'],true)) {
            $this->requirePost();
            $input=$this->readJson();
            $service=new ItaForecastService($this->db,$this->user);
            header('Cache-Control: no-store, private');
            if ($this->action==='forecast-export') {
                $dao=new ItaForecastObjects($this->db,$this->user);
                $document=!empty($input['plan_id']) ? $dao->saved(ItaInput::positiveInt($input['plan_id']),empty($input['category_id'])?0:ItaInput::positiveInt($input['category_id'])) : $dao->calculate($input);
                $format=ItaInput::text($input['format'] ?? '',5);
                $bytes=ItaForecastExport::render($document,$format);
                $service->auditExport($document);
                header('Content-Type: '.($format==='pdf'?'application/pdf':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
                header('Content-Disposition: attachment; filename="ita-budget-'.$document['year'].'.'.$format.'"');
                echo $bytes;
            } else {
                $data=$this->action==='forecast-config-save' ? $service->configure($input) : $service->save($input);
                $this->json(['success'=>true,'data'=>$data,'message'=>'Đã lưu. Snapshot kế hoạch chỉ đổi khi bạn yêu cầu tính lại.']);
            }
            return;
        }
        if ($this->action==='ticket-save') {
            $this->requirePost();
            $data = (new ItaTicketService($this->db,$this->user))->save($this->readJson());
            $this->json(['success'=>true,'data'=>$data,'message'=>'Đã cập nhật phiếu.']);
            return;
        }
        if (in_array($this->action,['license-save','license-key'],true)) {
            header('Cache-Control: no-store, private');
            $this->requirePost();
            $input = $this->readJson();
            $data = $this->action==='license-key'
                ? (new ItaLicenseReveal($this->db,$this->user,$this->config))->reveal(ItaInput::positiveInt($input['id'] ?? null),$input['password'] ?? null)
                : (new ItaLicenseService($this->db,$this->user))->save($input);
            $this->json(['success'=>true,'data'=>$data,'message'=>$this->action==='license-key'?'Key tự ẩn sau 10 giây.':'Đã cập nhật giấy phép.']);
            return;
        }
        if ($this->action==='assignment-pdf') {
            $this->handoverPdf();
            return;
        }
        if (in_array($this->action,['import-template','import-upload','import-preview','import-confirm','import-errors','asset-export'],true)) {
            (new ItaImportController($this->db,$this->user))->run($this->action);
            return;
        }
        $page = ItaInput::positiveInt($_GET['page'] ?? '1');
        if ($this->action==='assignment-save' && $_SERVER['REQUEST_METHOD']==='POST') {
            $this->requirePost();
            $data = (new ItaAssignmentService($this->db,$this->user))->save($this->readJson());
            $this->json(['success'=>true,'data'=>$data,'message'=>'Đã cập nhật bàn giao.']);
            return;
        }
        if (in_array($this->action,['asset-save','category-save'],true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $input = $this->readJson();
            $service = $this->action === 'asset-save' ? new ItaAssetService($this->db,$this->user) : new ItaCategoryService($this->db,$this->user);
            $id = $service->save($input);
            $this->json(['success'=>true,'data'=>['id'=>$id],'message'=>'Đã lưu thay đổi.']);
            return;
        }
        if ($this->action === 'access' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $data = $this->readJson(16384);
            (new ItaAccessService($this->db,$this->user))->update($data);
            $this->json(['success'=>true,'data'=>null,'message'=>'Đã cập nhật quyền truy cập.']);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw new ItaHttpError(405, 'Phương thức không được hỗ trợ.');
        }
        $assets = new ItaAssetObjects($this->db,$this->user);
        $data = match ($this->action) {
            'forecast' => (new ItaForecastObjects($this->db,$this->user))->calculate($_GET),
            'forecast-config' => (new ItaForecastObjects($this->db,$this->user))->configuration(),
            'forecast-plans' => (new ItaForecastObjects($this->db,$this->user))->plans($_GET),
            'forecast-plan' => (new ItaForecastObjects($this->db,$this->user))->saved(ItaInput::positiveInt($_GET['id'] ?? null),empty($_GET['category_id'])?0:ItaInput::positiveInt($_GET['category_id'])),
            'lifecycle' => (new ItaLifecycleObjects($this->db,$this->user))->listing($_GET,$page),
            'lifecycle-detail' => (new ItaLifecycleObjects($this->db,$this->user))->detail(ItaInput::positiveInt($_GET['id'] ?? null)),
            'tickets' => (new ItaTicketObjects($this->db,$this->user))->listing($_GET,$page),
            'ticket' => (new ItaTicketObjects($this->db,$this->user))->detail(ItaInput::positiveInt($_GET['id'] ?? null),$page),
            'ticket-technicians' => (new ItaTicketObjects($this->db,$this->user))->technicians(ItaInput::positiveInt($_GET['asset_id'] ?? null),$page),
            'ticket-statistics' => (new ItaTicketObjects($this->db,$this->user))->statistics($_GET),
            'licenses' => (new ItaLicenseObjects($this->db,$this->user))->listing($_GET,$page),
            'license' => (new ItaLicenseObjects($this->db,$this->user))->detail(ItaInput::positiveInt($_GET['id'] ?? null),$page),
            'data' => $this->overview($assets,$page),
            'asset' => $assets->detail(ItaInput::positiveInt($_GET['id'] ?? null),$page),
            'assignments' => (new ItaAssignmentObjects($this->db,$this->user))->detail(ItaInput::positiveInt($_GET['asset_id'] ?? null),$page),
            'assignment-recipients' => (new ItaAssignmentObjects($this->db,$this->user))->recipients(ItaInput::text($_GET['q'] ?? '',100),$page),
            'categories' => array_map(fn($r)=>$r+['version'=>ItaAssetService::version($r)],(new ItaCategoryService($this->db,$this->user))->listing()),
            'users' => (new ItaUserObjects($this->db))->listing($this->user,$page),
            'access' => (new ItaUserObjects($this->db))->access($this->user,ItaInput::positiveInt($_GET['id'] ?? null)),
            'audit' => (new ItaAuditService($this->db,$this->user))->listing($_GET,$page),
            default => throw new ItaHttpError(404, 'Không tìm thấy chức năng.'),
        };
        $this->json(['success'=>true,'data'=>$data,'message'=>'']);
    }

    private function reportApi(): void
    {
        header('Cache-Control: no-store, private');
        $service=new ItaReportService($this->db,$this->user,(string)$this->config['auth_key']);
        if ($this->action==='report-export') {
            $this->requirePost();$file=$service->export($this->readJson());
            header('X-Content-Type-Options: nosniff');
            header('Content-Type: '.($file['format']==='pdf'?'application/pdf':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
            header('Content-Disposition: attachment; filename="ita-'.$file['report'].'.'.$file['format'].'"');
            echo $file['bytes'];return;
        }
        if ($_SERVER['REQUEST_METHOD']!=='GET') { throw new ItaHttpError(405,'Chỉ hỗ trợ GET.'); }
        $page=ItaInput::positiveInt($_GET['page'] ?? '1');
        $data=$this->action==='report-preview'?$service->preview($_GET,$page):$service->read(function() use ($page) {
            $dao=new ItaDashboardObjects($this->db,$this->user,ItaReportService::now());
            return $this->action==='alerts'?$dao->alerts($_GET,$page):$dao->dashboard($_GET);
        });
        $this->json(['success'=>true,'data'=>$data,'message'=>'']);
    }

    private function overview(ItaAssetObjects $assets, int $page): array
    {
        $summary = $assets->summary();
        $ready = (new ItaTicketObjects($this->db,$this->user))->waitingAlerts();
        $summary['alerts'] = [...$summary['alerts'],...$ready['alerts']];
        $summary['alerts_total'] += $ready['total'];
        $summary['awaiting_ready_total'] = $ready['total'];
        if (in_array($this->user->role,['admin_it','system_manager'],true)) {
            $licenses = (new ItaLicenseObjects($this->db,$this->user))->alerts();
            $summary['alerts'] = [...$summary['alerts'],...$licenses['alerts']];
            $summary['alerts_total'] += $licenses['total'];
            $summary['license_counts'] = $licenses['counts'];
        }
        return $assets->listing($_GET,$page)+['summary'=>$summary,'departments'=>$assets->departments(),'mock'=>false,
            'user'=>['id'=>$this->user->id,'display_name'=>$this->user->name,'role'=>$this->user->role,'role_label'=>$this->user->roleLabel()]];
    }

    private function readJson(int $limit = 32768): array
    {
        if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            throw new ItaHttpError(415,'Yêu cầu cần dữ liệu JSON.');
        }
        $body = file_get_contents('php://input',false,null,0,$limit+1);
        $data = $body !== false && strlen($body)<=$limit ? json_decode($body,true) : null;
        if (!is_array($data) || array_is_list($data)) {
            throw new ItaHttpError(422,'Dữ liệu không hợp lệ hoặc vượt giới hạn.');
        }
        return $data;
    }

    private function handoverPdf(): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='GET') {
            throw new ItaHttpError(405,'Tải PDF cần phương thức GET.');
        }
        $id = ItaInput::positiveInt($_GET['id'] ?? null);
        $bytes = (new ItaHandoverService($this->db,$this->user))->download($id);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ita-handover-'.$id.'.pdf"');
        header('Content-Length: '.strlen($bytes));
        echo $bytes;
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new ItaHttpError(405, 'Thao tác này cần POST.');
        }
        ItaSession::requireCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null);
    }

    private function unauthenticated(): void
    {
        if (in_array($this->action, ['', 'logout'], true)) {
            header('Location: /admin.php?op=ita&act=login', true, 303);
        } else {
            throw new ItaHttpError(401, 'Vui lòng đăng nhập lại.');
        }
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function error(int $status, string $message): void
    {
        $reference=ItaErrorResponse::report($status);
        if ($this->action==='login' && session_status()===PHP_SESSION_ACTIVE) {
            http_response_code($status);
            if ($status===429) { header('Retry-After: 900'); }
            $level=ob_get_level();ob_start();
            try {
                $this->render('admin/ita/login.tpl.html', ['itaCsrf'=>$_SESSION['ita_csrf'],'itaError'=>$message.' Mã tham chiếu: '.$reference]);
                $html=ob_get_clean();echo $html;return;
            } catch (Throwable) {
                while (ob_get_level()>$level) { ob_end_clean(); }
                ItaErrorResponse::send(500,'Không thể mở trang đăng nhập. Vui lòng thử lại sau.',false,$reference);return;
            }
        }
        $requested=$_GET['act'] ?? $this->action;
        ItaErrorResponse::send($status,$message,!is_string($requested) || ($requested!=='' && !in_array($requested,['login','logout'],true)),$reference);
    }

    private function render(string $file, array $values): void
    {
        require_once ROOT_PATH . 'classes/template/smarty.class.php';
        $directory = sys_get_temp_dir().'/infracore-smarty-'.substr(hash('sha256',ROOT_PATH),0,12);
        if (!is_dir($directory) && !mkdir($directory,0770,true) && !is_dir($directory)) {
            throw new RuntimeException('ITA template cache unavailable');
        }
        $template = new Smarty();
        $template->setTemplateDir(ROOT_PATH.'templates');
        $template->setCompileDir($directory);
        $template->assign($values + ['itaRenderer'=>'smarty']);
        header('Content-Type: text/html; charset=utf-8');
        echo $template->fetch($file);
    }
}
