<?php
class ItaAuthService
{
    public function __construct(private ItaDb $db, private array $config)
    {
    }

    public function authenticate(string $identity, string $password, string $address): ItaUserObjectInfo
    {
        $users = new ItaUserObjects($this->db);
        $row = $users->identity((int) $this->config['store_id'], $identity);
        $key = $row ? 'user:'.$row['id'] : 'unknown:'.mb_strtolower($identity);
        $limiter = new ItaLoginLimiter($this->db, (int) $this->config['store_id'], $this->config['auth_key']);
        $limiter->reserve($key, $address);
        $user = $row ? $users->current((int) $this->config['store_id'], (int) $row['id']) : null;
        if (!$user) {
            password_verify($password, password_hash('ITA invalid account timing check', PASSWORD_DEFAULT));
            throw new ItaHttpError(401, 'Thông tin đăng nhập không hợp lệ hoặc tài khoản chưa được cấp quyền IT.');
        }
        if (!$this->verifyPassword($user, $identity, $password)) {
            throw new ItaHttpError(401, 'Thông tin đăng nhập không hợp lệ hoặc tài khoản chưa được cấp quyền IT.');
        }
        (new ItaAuditService($this->db, $user))->record('auth.login', 'user', $user->id);
        $limiter->clearIdentity($key);
        return $user;
    }

    /** Preserve long legacy credentials without migrating them to a truncated bcrypt hash. */
    private function verifyPassword(ItaUserObjectInfo $user, string $identity, string $password): bool
    {
        if (strlen($password) <= 72) {
            return (new PmAuth($this->db->legacyDatabase()))->authenticate($user->storeId, $identity, $password) === $user->id;
        }
        $row = $this->db->fetchOne('SELECT password,password_hash FROM dc_users WHERE store_id=? AND id=? AND status=1', 'ii', [$user->storeId,$user->id]);
        if (!$row) {
            return false;
        }
        $modern = (string) ($row['password_hash'] ?? '');
        // Existing bcrypt hashes cannot distinguish suffixes beyond byte 72.
        if (str_starts_with($modern, '$2')) {
            return false;
        }
        $legacy = (string) ($row['password'] ?? '');
        $verified = $modern !== '' ? password_verify($password, $modern)
            : strlen($legacy) === 32 && hash_equals(strtolower($legacy), md5($password));
        if ($verified) {
            $this->db->execute('UPDATE dc_users SET last_login=NOW() WHERE store_id=? AND id=?', 'ii', [$user->storeId,$user->id]);
        }
        return $verified;
    }

    public function current(): ?ItaUserObjectInfo
    {
        $id = $_SESSION['ita_user_id'] ?? null;
        if (!is_int($id) || ($_SESSION['ita_store_id'] ?? 0) !== (int) $this->config['store_id']) {
            return null;
        }
        $user = (new ItaUserObjects($this->db))->current((int) $this->config['store_id'], $id);
        if (!$user || !ItaSession::valid($user)) {
            ItaSession::logout();
            return null;
        }
        $_SESSION['ita_seen'] = time();
        return $user;
    }
}
