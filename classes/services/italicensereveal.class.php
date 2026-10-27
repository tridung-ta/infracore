<?php
class ItaLicenseReveal
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor, private array $config) {}

    /** Called only by POST+CSRF dispatcher. Audit is durable before plaintext can leave the service. */
    public function reveal(int $id, mixed $password): array
    {
        $this->actor->requireCapability('license.reveal');
        if (!is_string($password) || $password==='' || strlen($password)>1024 || str_contains($password,"\0")) {
            throw new ItaHttpError(422,'Nhập lại mật khẩu để xem key.');
        }
        $limiter = new ItaLoginLimiter($this->db,$this->actor->storeId,$this->config['auth_key']);
        $limiter->reserve('license-reveal:'.$this->actor->id,'license-reveal:'.($_SERVER['REMOTE_ADDR'] ?? ''));
        $credential = $this->db->fetchOne('SELECT password,password_hash FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->actor->storeId,$this->actor->id]);
        if (!$credential || !self::verify($credential,$password)) {
            throw new ItaHttpError(401,'Mật khẩu xác nhận không đúng.');
        }
        unset($password,$credential);
        $this->db->beginTransaction();
        try {
            $current = (new ItaUserObjects($this->db))->current($this->actor->storeId,$this->actor->id);
            $role = $this->db->fetchOne('SELECT role,enabled,auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=? FOR UPDATE','ii',[$this->actor->storeId,$this->actor->id]);
            if (!$current || !$current->can('license.reveal') || !$role || !(int) $role['enabled'] || $role['role']!==$this->actor->role || (int) $role['auth_revision']!==$this->actor->revision) {
                throw new ItaHttpError(403,'Quyền đã thay đổi. Vui lòng đăng nhập lại.');
            }
            $row = (new ItaLicenseObjects($this->db,$current))->row($id,true);
            if (empty($row['license_key_enc'])) {
                throw new ItaHttpError(404,'Giấy phép chưa có key.');
            }
            $plain = ItaLicenseCrypto::configured()->decrypt($row['license_key_enc'],'ita-license-store:'.$current->storeId);
            (new ItaAuditService($this->db,$current))->record('license.key_view','license',$id);
            $this->db->commit();
            return ['key'=>$plain,'hide_after_seconds'=>10];
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public static function verify(array $credential, string $password): bool
    {
        $modern = (string) ($credential['password_hash'] ?? '');
        if ($modern!=='') {
            return !(str_starts_with($modern,'$2') && strlen($password)>72) && password_verify($password,$modern);
        }
        $legacy = (string) ($credential['password'] ?? '');
        return preg_match('/^[a-f0-9]{32}$/iD',$legacy)===1 && hash_equals(strtolower($legacy),md5($password));
    }
}
