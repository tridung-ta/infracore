<?php
class ItaAuthService
{
    // Generated once with password_hash(PASSWORD_BCRYPT), never a user credential.
    private const DUMMY_HASHES = [
        '$2y$10$XoWL3z/qTv8PZOUefifCzO.ENtzml8.O/bHhQYZ0SWE2XDz/8Duim',
        '$2y$12$hcO4kb1UrAjsWBfA.BAB8OQcHV/Shs5VzK1QSBL2tz2ZiE/rQT1DS',
    ];
    private const REJECTED = 'Thông tin đăng nhập không hợp lệ hoặc tài khoản chưa được cấp quyền IT.';

    public function __construct(private ItaDb $db, private array $config)
    {
    }

    public function authenticate(string $identity, string $password, string $address): ItaUserObjectInfo
    {
        $identity = ItaInput::text($identity, 200);
        if ($identity === '' || $password === '' || strlen($password)>1024 || str_contains($password,"\0")) {
            throw new ItaHttpError(422, 'Vui lòng nhập thông tin đăng nhập hợp lệ.');
        }
        $users = new ItaUserObjects($this->db);
        $row = $users->identity((int) $this->config['store_id'], $identity);
        $key = $row ? 'user:'.$row['id'] : 'unknown:'.mb_strtolower($identity);
        $limiter = new ItaLoginLimiter($this->db, (int) $this->config['store_id'], $this->config['auth_key']);
        $limiter->reserve($key, $address);
        $user = $row ? $users->current((int) $this->config['store_id'], (int) $row['id']) : null;
        $credential = $user ? $this->credential($user) : null;
        if (!$this->verifyPassword($credential, $password) || !$user) {
            throw new ItaHttpError(401, self::REJECTED);
        }
        // Rejection checks precede all native writes; never verify a second time.
        $current = $users->current($user->storeId, $user->id);
        $identityRow = $users->identity($user->storeId, $identity);
        if (!$current || !$identityRow || (int)$identityRow['id']!==$user->id
            || !$this->sameAccess($current,$user) || $this->credential($user)!==$credential) {
            throw new ItaHttpError(401, self::REJECTED);
        }
        $this->recordNativeLogin($user, $credential, $password);
        (new ItaAuditService($this->db, $user))->record('auth.login', 'user', $user->id);
        $limiter->clearIdentity($key);
        return $user;
    }

    private function credential(ItaUserObjectInfo $user): ?array
    {
        $row=$this->db->fetchOne('SELECT password,password_hash FROM dc_users WHERE store_id=? AND id=? AND status=1', 'ii', [$user->storeId,$user->id]);
        return $row ? ['password'=>(string)($row['password'] ?? ''),'password_hash'=>(string)($row['password_hash'] ?? '')] : null;
    }

    private function sameAccess(ItaUserObjectInfo $current, ItaUserObjectInfo $before): bool
    {
        $a=$current->departmentIds;$b=$before->departmentIds;sort($a);sort($b);
        return $current->id===$before->id && $current->storeId===$before->storeId
            && $current->role===$before->role && $current->revision===$before->revision && $a===$b;
    }

    /** Match unchanged PmAuth's PASSWORD_DEFAULT algorithm/cost without per-request hashing. */
    protected function dummyHash(): string
    {
        foreach (self::DUMMY_HASHES as $hash) {
            if (!password_needs_rehash($hash, PASSWORD_DEFAULT)) { return $hash; }
        }
        throw new ItaHttpError(503, 'Cấu hình xác thực chưa được hỗ trợ.');
    }

    /** Single verifier boundary; tests count it and any downstream PmAuth calls separately. */
    protected function verifyHash(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function verifyPassword(?array $row, string $password): bool
    {
        $modern=(string)($row['password_hash'] ?? '');
        $algorithm=password_get_info($modern)['algoName'];
        // PHP verifies 2a/2b/2x too, although password_get_info may call them unknown.
        $bcrypt=preg_match('/^\$2[abxy]\$(?:0[4-9]|[12][0-9]|3[01])\$[.\/A-Za-z0-9]{53}$/D',$modern)===1;
        $real=$row && ($bcrypt || in_array($algorithm,['argon2i','argon2id'],true))
            && !($bcrypt && strlen($password)>72);
        $verified=$this->verifyHash($password, $real ? $modern : $this->dummyHash());
        if (!$row || ($modern!=='' && !$real)) { return false; }
        if ($modern!=='') { return $verified; }
        $legacy=$row['password'];
        return strlen($legacy)===32 && hash_equals(strtolower($legacy),md5($password));
    }

    /** Preserve PmAuth success writes, including the no-bcrypt-upgrade rule above72 bytes. */
    private function recordNativeLogin(ItaUserObjectInfo $user, array $row, string $password): void
    {
        $where=" WHERE store_id = ? AND id = ? AND status=1 AND BINARY COALESCE(password,'')=BINARY ? AND BINARY COALESCE(password_hash,'')=BINARY ?";
        if (strlen($password)<=72 && ($row['password_hash']==='' || password_needs_rehash($row['password_hash'],PASSWORD_DEFAULT))) {
            $hash=password_hash($password,PASSWORD_DEFAULT);
            $changed=$this->db->execute('UPDATE dc_users SET password_hash = ?, last_login = NOW()'.$where, 'siiss', [$hash,$user->storeId,$user->id,$row['password'],$row['password_hash']]);
        } else {
            $changed=$this->db->execute('UPDATE dc_users SET last_login = NOW()'.$where, 'iiss', [$user->storeId,$user->id,$row['password'],$row['password_hash']]);
        }
        // A same-second last_login may legitimately affect zero rows; changed credentials must still reject.
        if ($changed!==1 && $this->credential($user)!==$row) { throw new ItaHttpError(401,self::REJECTED); }
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
