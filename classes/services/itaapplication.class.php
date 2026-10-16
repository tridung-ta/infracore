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
            error_log('ITA request unavailable.');
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
        $page = ItaInput::positiveInt($_GET['page'] ?? '1');
        if ($this->action === 'access' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
                throw new ItaHttpError(415, 'Yêu cầu cần dữ liệu JSON.');
            }
            $body = file_get_contents('php://input', false, null, 0, 16385);
            $data = strlen($body)>16384 ? null : json_decode($body, true);
            if (!is_array($data)) {
                throw new ItaHttpError(422, 'Dữ liệu quyền không hợp lệ.');
            }
            (new ItaAccessService($this->db,$this->user))->update($data);
            $this->json(['success'=>true,'data'=>null,'message'=>'Đã cập nhật quyền truy cập.']);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw new ItaHttpError(405, 'Phương thức không được hỗ trợ.');
        }
        $assets = new ItaAssetObjects($this->db,$this->user);
        $data = match ($this->action) {
            'data' => $assets->listing($_GET,$page) + ['summary'=>$assets->summary(),'departments'=>$assets->departments(),
                'mock'=>false,'user'=>['id'=>$this->user->id,'display_name'=>$this->user->name,'role'=>$this->user->role,'role_label'=>$this->user->roleLabel()]],
            'asset' => $assets->detail(ItaInput::positiveInt($_GET['id'] ?? null)),
            'users' => (new ItaUserObjects($this->db))->listing($this->user,$page),
            'access' => (new ItaUserObjects($this->db))->access($this->user,ItaInput::positiveInt($_GET['id'] ?? null)),
            'audit' => (new ItaAuditService($this->db,$this->user))->listing($_GET,$page),
            default => throw new ItaHttpError(404, 'Không tìm thấy chức năng.'),
        };
        $this->json(['success'=>true,'data'=>$data,'message'=>'']);
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
        http_response_code($status);
        if ($status===429) {
            header('Retry-After: 900');
        }
        if ($this->action==='login' && session_status()===PHP_SESSION_ACTIVE) {
            $this->render('admin/ita/login.tpl.html', ['itaCsrf'=>$_SESSION['ita_csrf'],'itaError'=>$message]);
        } else {
            $this->json(['success'=>false,'data'=>null,'message'=>$message]);
        }
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
