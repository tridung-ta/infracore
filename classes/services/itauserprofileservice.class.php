<?php
require_once __DIR__.'/itausercreationinput.class.php';

/** Native accounts are shared and may use MyISAM; mutations cannot roll back atomically with ITA audit. */
class ItaUserProfileService
{
    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor,private array $config) {}

    public function read(int $id): array
    {
        $this->authorize();$snapshot=$this->snapshot($id);
        return ['id'=>$id,'username'=>$snapshot['native']['username'],'fullname'=>$snapshot['native']['fullname'],
            'email'=>$snapshot['native']['email'],'version'=>$this->version($snapshot)];
    }

    public function update(array $input): void
    {
        $this->authorize();$id=ItaInput::positiveInt($input['user_id'] ?? null);
        $values=$this->validateProfile($input);$this->checkWidths($values);
        $this->mutate($id,$input,function(array $snapshot) use ($id,$values): void {
            $native=$snapshot['native'];
            if ($values['username']===$native['username'] && $values['fullname']===$native['fullname'] && $values['email']===$native['email']) {
                throw new ItaHttpError(422,'Thông tin chưa thay đổi. Hãy chỉnh sửa trước khi lưu.');
            }
            $duplicates=$this->db->fetchAll('SELECT id FROM dc_users WHERE store_id=? AND id<>? AND (username IN (?,?) OR email IN (?,?)) LIMIT 2',
                'iissss',[$this->actor->storeId,$id,$values['username'],$values['email'],$values['username'],$values['email']]);
            if ($duplicates) { throw new ItaHttpError(409,'Tên đăng nhập hoặc email đã được tài khoản khác sử dụng.'); }
            (new ItaAuditService($this->db,$this->actor))->record('user.profile_update','user',$id,
                ['before'=>['name'=>$native['fullname']],'after'=>['name'=>$values['fullname'],'note'=>'Thông tin tài khoản nền đã được cập nhật.']]);
            ItaPermissions::recheck($this->db,$this->actor,'access.manage');
            $this->nativeAttempted=true;
            $affected=$this->db->execute("UPDATE dc_users SET username=?,fullname=?,email=? WHERE store_id=? AND id=? AND type=1 AND status=? AND BINARY COALESCE(username,'')=BINARY ? AND BINARY COALESCE(fullname,'')=BINARY ? AND BINARY COALESCE(email,'')=BINARY ?",
                'sssiiisss',[$values['username'],$values['fullname'],$values['email'],$this->actor->storeId,$id,(int)$native['status'],$native['username'],$native['fullname'],$native['email']]);
            if ($affected!==1) { $this->nativeAttempted=false;throw new ItaHttpError(409,'Thông tin tài khoản đã thay đổi. Hãy tải lại trước khi lưu.'); }
        });
    }

    public function resetPassword(array $input,string $address): void
    {
        $this->authorize();$id=ItaInput::positiveInt($input['user_id'] ?? null);
        $password=$input['password'] ?? null;$confirmation=$input['password_confirmation'] ?? null;
        if (!is_string($password) || !mb_check_encoding($password,'UTF-8') || mb_strlen($password,'UTF-8')<6 || strlen($password)>72
            || str_contains($password,"\0") || preg_match('/\A[\s\p{Z}\p{Cf}]*\z/u',$password)
            || !is_string($confirmation) || !hash_equals($password,$confirmation)) {
            throw new ItaHttpError(422,'Mật khẩu mới cần ít nhất 6 ký tự, tối đa 72 byte, không chỉ khoảng trắng; nhập lại phải khớp.');
        }
        $actorCredential=$this->reauthenticate($input['actor_password'] ?? null,$address);
        $newHash=password_hash($password,PASSWORD_DEFAULT);$placeholder=bin2hex(random_bytes(16));$newPassword=$password;unset($password,$confirmation);
        $this->checkWidths(['password_hash'=>$newHash,'password'=>$placeholder]);
        $this->mutate($id,$input,function(array $snapshot) use ($id,$newHash,$placeholder,$actorCredential,$newPassword): void {
            $native=$snapshot['native'];
            $matchesLegacy=preg_match('/\A[a-f0-9]{32}\z/iD',$native['password']) && hash_equals(strtolower($native['password']),md5($newPassword));
            if (ItaLicenseReveal::verify($native,$newPassword) || $matchesLegacy) { throw new ItaHttpError(422,'Mật khẩu mới phải khác mật khẩu hiện tại và mật khẩu cũ của tài khoản.'); }
            $role=$this->db->fetchOne('SELECT auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=?','ii',[$this->actor->storeId,$id]);
            if (!$role || (int)$role['auth_revision']>=4294967295) { throw new ItaHttpError(503,'Phiên bản bảo vệ tài khoản đã đạt giới hạn. Cần quản trị kiểm tra trước khi đặt lại mật khẩu.'); }
            (new ItaAuditService($this->db,$this->actor))->record('user.password_reset','user',$id);
            ItaPermissions::recheck($this->db,$this->actor,'access.manage');
            if (!hash_equals($actorCredential,$this->actorCredentialFingerprint())) { throw new ItaHttpError(403,'Mật khẩu hoặc trạng thái quản trị đã thay đổi. Hãy đăng nhập lại.'); }
            $this->nativeAttempted=true;
            $affected=$this->db->execute("UPDATE dc_users SET password_hash=?,password=? WHERE store_id=? AND id=? AND type=1 AND status=? AND BINARY COALESCE(password_hash,'')=BINARY ? AND BINARY COALESCE(password,'')=BINARY ?",
                'ssiiiss',[$newHash,$placeholder,$this->actor->storeId,$id,(int)$native['status'],$native['password_hash'],$native['password']]);
            if ($affected!==1) { $this->nativeAttempted=false;throw new ItaHttpError(409,'Mật khẩu tài khoản đã thay đổi. Hãy tải lại trước khi đặt lại.'); }
            $revised=$this->db->execute('UPDATE ita_user_roles SET auth_revision=auth_revision+1,updated_by=? WHERE store_id=? AND user_id=? AND auth_revision<4294967295',
                'iii',[$this->actor->id,$this->actor->storeId,$id]);
            if ($revised!==1) { throw new ItaHttpError(503,'Không xác nhận được phiên bản bảo vệ tài khoản.'); }
        });
    }

    private bool $nativeAttempted=false;

    private function mutate(int $id,array $input,callable $operation): void
    {
        $this->nativeAttempted=false;$this->db->beginTransaction();
        try {
            $this->db->fetchAll('SELECT user_id,enabled,auth_revision FROM ita_user_roles WHERE store_id=? ORDER BY user_id FOR UPDATE','i',[$this->actor->storeId]);
            ItaPermissions::recheck($this->db,$this->actor,'access.manage');$snapshot=$this->snapshot($id);
            $version=ItaInput::text($input['version'] ?? '',64);
            if (!hash_equals($this->version($snapshot),$version)) { throw new ItaHttpError(409,'Tài khoản đã thay đổi. Hãy mở lại trước khi lưu.'); }
            $operation($snapshot);$this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($this->nativeAttempted) { throw new ItaHttpError(503,'Kết quả cập nhật tài khoản #'.$id.' chưa rõ. Không gửi lại; hãy kiểm tra tài khoản và nhật ký trước khi tiếp tục.'); }
            throw $error;
        }
    }

    private function authorize(): void
    {
        if (!$this->actor->capabilitiesEnabled) { throw new ItaHttpError(503,'Cần bật quyền chức năng để quản lý tài khoản nền.'); }
        $this->actor->requireCapability('access.manage');$this->actor->requireCapability('scope.store');
    }

    private function snapshot(int $id): array
    {
        $access=(new ItaUserObjects($this->db))->access($this->actor,$id);
        if (!in_array($access['role'] ?? null,['admin_it','system_manager','support_technician'],true)) { throw new ItaHttpError(403,'Tài khoản chưa thuộc phạm vi quản lý InfraCore.'); }
        if (array_diff($access['permissions'],$this->actor->permissions)) { throw new ItaHttpError(403,'Tài khoản có quyền vượt phạm vi bạn được ủy quyền quản lý.'); }
        $row=$this->db->fetchOne('SELECT id,username,fullname,email,password,password_hash,status,type FROM dc_users WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
        if (!$row) { throw new ItaHttpError(404,'Không tìm thấy tài khoản.'); }
        if ((string)$row['type']!=='1') { throw new ItaHttpError(403,'Chỉ hỗ trợ sửa tài khoản nhân viên nền; tài khoản quản trị hệ thống cũ cần quản lý theo quy trình riêng.'); }
        $origin=$this->db->fetchOne("SELECT id FROM ita_audit_logs WHERE store_id=? AND entity_type='user' AND entity_id=? AND action='user.create' LIMIT 1",'ii',[$this->actor->storeId,$id]);
        if (!$origin) { throw new ItaHttpError(403,'Chỉ được sửa hoặc đặt lại mật khẩu tài khoản được tạo từ InfraCore và có nhật ký nguồn gốc.'); }
        foreach (['username','fullname','email','password','password_hash'] as $key) { $row[$key]=(string)($row[$key] ?? ''); }
        return ['native'=>$row,'access'=>$access];
    }

    private function version(array $snapshot): string
    {
        $native=$snapshot['native'];$secret=$this->config['auth_key'] ?? '';
        if (!is_string($secret) || $secret==='') { throw new ItaHttpError(503,'Cấu hình bảo vệ phiên chưa sẵn sàng.'); }
        return hash_hmac('sha256',json_encode([$native,$snapshot['access']['version']],JSON_THROW_ON_ERROR),$secret);
    }

    private function validateProfile(array $input): array
    {
        $username=ItaInput::text($input['username'] ?? '',50);$fullname=ItaInput::text($input['fullname'] ?? '',100);$email=ItaInput::text($input['email'] ?? '',100);
        if (!preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]{2,49}\z/D',$username)) { throw new ItaHttpError(422,'Tên đăng nhập cần 3–50 ký tự không dấu và không có khoảng trắng.'); }
        if ($fullname==='' || preg_match('/\A[\s\p{Z}\p{Cf}]*\z/u',$fullname) || preg_match('/\p{Cc}/u',$fullname)) { throw new ItaHttpError(422,'Họ tên không được để trống hoặc chứa ký tự điều khiển.'); }
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { throw new ItaHttpError(422,'Email không hợp lệ. Hãy nhập địa chỉ đúng định dạng.'); }
        return ['username'=>$username,'fullname'=>$fullname,'email'=>$email];
    }

    private function checkWidths(array $values): void
    {
        $columns=array_column($this->db->fetchAll('SHOW COLUMNS FROM dc_users'),null,'Field');
        foreach ($values as $field=>$value) {
            if (!isset($columns[$field]) || !preg_match('/^(?:var)?char\((\d+)\)/i',$columns[$field]['Type'] ?? '',$match)) { throw new ItaHttpError(503,'Cấu trúc tài khoản nền chưa hỗ trợ chỉnh sửa.'); }
            if (mb_strlen($value,'UTF-8')>(int)$match[1]) {
                if (in_array($field,['password','password_hash'],true)) { throw new ItaHttpError(503,'Cấu trúc lưu mật khẩu nền chưa hỗ trợ định dạng bảo vệ hiện tại.'); }
                $label=['username'=>'Tên đăng nhập','fullname'=>'Họ tên','email'=>'Email'][$field];
                throw new ItaHttpError(422,$label.' vượt giới hạn '.$match[1].' ký tự của hệ thống nền.');
            }
        }
    }

    private function reauthenticate(mixed $password,string $address): string
    {
        if (!is_string($password) || $password==='' || strlen($password)>1024 || str_contains($password,"\0")) { throw new ItaHttpError(422,'Nhập lại mật khẩu quản trị để xác nhận.'); }
        $limiter=new ItaLoginLimiter($this->db,$this->actor->storeId,$this->config['auth_key']);
        $limiter->reserve('account-reset:'.$this->actor->id,'account-reset:'.$address);
        $credential=$this->db->fetchOne('SELECT password,password_hash FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->actor->storeId,$this->actor->id]);
        if (!$credential || !ItaLicenseReveal::verify($credential,$password)) { throw new ItaHttpError(401,'Mật khẩu quản trị xác nhận không đúng.'); }
        $limiter->clearIdentity('account-reset:'.$this->actor->id);
        return $this->credentialFingerprint($credential);
    }

    private function actorCredentialFingerprint(): string
    {
        $credential=$this->db->fetchOne('SELECT password,password_hash FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->actor->storeId,$this->actor->id]);
        if (!$credential) { throw new ItaHttpError(403,'Tài khoản quản trị không còn hoạt động.'); }
        return $this->credentialFingerprint($credential);
    }

    private function credentialFingerprint(array $credential): string
    {
        return hash_hmac('sha256',json_encode([$credential['password'] ?? '', $credential['password_hash'] ?? ''],JSON_THROW_ON_ERROR),$this->config['auth_key']);
    }
}
