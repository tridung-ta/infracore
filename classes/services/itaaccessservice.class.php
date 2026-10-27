<?php
class ItaAccessService
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    public function update(array $input): void
    {
        if ($this->actor->capabilitiesEnabled) {
            require_once __DIR__.'/itacapabilityaccessservice.class.php';
            (new ItaCapabilityAccessService($this->db,$this->actor))->update($input);
            return;
        }
        $this->actor->requireAdmin();
        $userId = ItaInput::positiveInt($input['user_id'] ?? null);
        $role = $input['role'] ?? '';
        $enabled = $input['enabled'] ?? null;
        $scopes = $input['department_ids'] ?? null;
        if (!in_array($role, ['admin_it','system_manager','support_technician'], true) || !is_bool($enabled)
            || !is_array($scopes) || count($scopes) > 500) {
            throw new ItaHttpError(422, 'Vai trò hoặc bộ phận không hợp lệ.');
        }
        $scopes = array_values(array_unique(array_map([ItaInput::class,'positiveInt'], $scopes)));
        sort($scopes);
        if ($enabled && $role !== 'admin_it' && !$scopes) {
            throw new ItaHttpError(422, 'Vai trò này cần ít nhất một bộ phận phụ trách.');
        }
        $target = (new ItaUserObjects($this->db))->access($this->actor, $userId);
        if ($enabled && (int) $target['status'] !== 1) {
            throw new ItaHttpError(422, 'Tài khoản nền đang ngưng hoạt động.');
        }
        $this->validateScopes($scopes);
        $this->db->beginTransaction();
        try {
            $rows = $this->db->fetchAll('SELECT r.user_id,r.role,r.enabled,r.auth_revision FROM ita_user_roles r
                WHERE r.store_id=? AND EXISTS (SELECT 1 FROM dc_users u WHERE u.store_id=r.store_id AND u.id=r.user_id AND u.status=1) FOR UPDATE', 'i', [$this->actor->storeId]);
            $this->validateActorAndLastAdmin($rows, $userId, $role, $enabled);
            $before = (new ItaUserObjects($this->db))->access($this->actor, $userId);
            $this->write($userId, $role, ['enabled'=>$enabled,'scopes'=>$scopes]);
            (new ItaAuditService($this->db, $this->actor))->record('user.access_update', 'user', $userId,
                ['before'=>$before,'after'=>['role'=>$role,'enabled'=>$enabled,'department_ids'=>$scopes]]);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    private function validateScopes(array $scopes): void
    {
        if (!$scopes) {
            return;
        }
        $marks = implode(',', array_fill(0, count($scopes), '?'));
        $row = $this->db->fetchOne("SELECT COUNT(*) n FROM ita_departments WHERE store_id=? AND status='active' AND id IN ($marks)",
            str_repeat('i', count($scopes)+1), [$this->actor->storeId,...$scopes]);
        if ((int) $row['n'] !== count($scopes)) {
            throw new ItaHttpError(422, 'Có bộ phận không thuộc phạm vi hệ thống.');
        }
    }

    private function validateActorAndLastAdmin(array $rows, int $userId, string $role, bool $enabled): void
    {
        $admins = [];
        $actorValid = false;
        foreach ($rows as $row) {
            if ($row['role'] === 'admin_it' && (int) $row['enabled'] === 1) {
                $admins[] = (int) $row['user_id'];
                if ((int) $row['user_id'] === $this->actor->id && (int) $row['auth_revision'] === $this->actor->revision) {
                    $actorValid = true;
                }
            }
        }
        if (!$actorValid) {
            throw new ItaHttpError(403, 'Quyền quản trị đã thay đổi. Vui lòng đăng nhập lại.');
        }
        if (in_array($userId, $admins, true) && count($admins) === 1 && (!$enabled || $role !== 'admin_it')) {
            throw new ItaHttpError(409, 'Không thể thu hồi Admin IT cuối cùng.');
        }
    }

    private function write(int $userId, string $role, array $values): void
    {
        $this->db->execute('INSERT INTO ita_user_roles (store_id,user_id,role,enabled,updated_by) VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE role=?,enabled=?,updated_by=?,auth_revision=auth_revision+1', 'iisiisii',
            [$this->actor->storeId,$userId,$role,(int) $values['enabled'],$this->actor->id,$role,(int) $values['enabled'],$this->actor->id]);
        $this->db->execute('UPDATE ita_user_scopes SET active=0 WHERE store_id=? AND user_id=?', 'ii', [$this->actor->storeId,$userId]);
        foreach ($values['scopes'] as $departmentId) {
            $this->db->execute('INSERT INTO ita_user_scopes (store_id,user_id,department_id,active) VALUES (?,?,?,1)
                ON DUPLICATE KEY UPDATE active=1', 'iii', [$this->actor->storeId,$userId,$departmentId]);
        }
    }
}
