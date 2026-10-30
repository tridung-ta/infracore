<?php
/** Capability-only writer. The existing role writer remains unchanged while the feature is off. */
class ItaCapabilityAccessService
{
    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor) {}

    public function update(array $input): void
    {
        $this->actor->requireCapability('access.manage');
        [$id,$enabled,$scopes,$permissions]=$this->validate($input);
        $this->db->beginTransaction();
        try {
            $rows=$this->db->fetchAll('SELECT user_id,enabled,auth_revision FROM ita_user_roles WHERE store_id=? ORDER BY user_id FOR UPDATE','i',[$this->actor->storeId]);
            $this->checkActor($rows);
            $before=(new ItaUserObjects($this->db))->access($this->actor,$id);
            if (array_diff($before['permissions'],$this->actor->permissions)) {
                throw new ItaHttpError(403,'Tài khoản có quyền vượt phạm vi bạn được ủy quyền quản lý.');
            }
            if (!hash_equals($before['version'],ItaInput::text($input['version'] ?? '',64))) {
                throw new ItaHttpError(409,'Quyền tài khoản đã thay đổi. Hãy mở lại trước khi lưu.');
            }
            if ($enabled && (int)$before['status']!==1) { throw new ItaHttpError(422,'Tài khoản nền không hoạt động.'); }
            $this->lastManager($id,$enabled,$permissions);
            $this->write($id,$before['role'] ?: 'support_technician',[$enabled,$scopes,$permissions]);
            (new ItaAuditService($this->db,$this->actor))->record('user.access_update','user',$id,
                ['before'=>$before,'after'=>['enabled'=>$enabled,'department_ids'=>$scopes,'permissions'=>$permissions]]);
            $this->db->commit();
        } catch (Throwable $error) { $this->db->rollBack();throw $error; }
    }

    /** Revoke ITA access without rewriting scope or touching the shared native account. */
    public function revoke(array $input): void
    {
        $this->actor->requireCapability('access.manage');$id=ItaInput::positiveInt($input['user_id'] ?? null);
        $this->db->beginTransaction();
        try {
            $rows=$this->db->fetchAll('SELECT user_id,enabled,auth_revision FROM ita_user_roles WHERE store_id=? ORDER BY user_id FOR UPDATE','i',[$this->actor->storeId]);
            $this->checkActor($rows);$before=(new ItaUserObjects($this->db))->access($this->actor,$id);
            if (array_diff($before['permissions'],$this->actor->permissions)) { throw new ItaHttpError(403,'Tài khoản có quyền vượt phạm vi bạn được ủy quyền quản lý.'); }
            if (!hash_equals($before['version'],ItaInput::text($input['version'] ?? '',64))) { throw new ItaHttpError(409,'Quyền tài khoản đã thay đổi. Hãy mở lại trước khi xóa quyền.'); }
            if ((int)($before['enabled'] ?? 0)!==1) { throw new ItaHttpError(409,'Tài khoản đã không còn quyền truy cập ITA.'); }
            $this->lastManager($id,false,[]);
            foreach ($rows as $row) { if ((int)$row['user_id']===$id && (int)$row['auth_revision']>=4294967295) { throw new ItaHttpError(503,'Phiên bản bảo vệ tài khoản đã đạt giới hạn. Cần quản trị kiểm tra trước khi xóa quyền.'); } }
            $changed=$this->db->execute('UPDATE ita_user_roles SET enabled=0,auth_revision=auth_revision+1,updated_by=? WHERE store_id=? AND user_id=? AND enabled=1 AND auth_revision<4294967295','iii',[$this->actor->id,$this->actor->storeId,$id]);
            if ($changed!==1) { throw new ItaHttpError(409,'Quyền tài khoản đã thay đổi. Hãy tải lại danh sách.'); }
            (new ItaAuditService($this->db,$this->actor))->record('user.access_update','user',$id,['before'=>['enabled'=>true],'after'=>['enabled'=>false]]);
            $this->db->commit();
        } catch (Throwable $error) { $this->db->rollBack();throw $error; }
    }

    private function validate(array $input): array
    {
        $id=ItaInput::positiveInt($input['user_id'] ?? null);$enabled=$input['enabled'] ?? null;
        $raw=$input['department_ids'] ?? null;
        if (!is_bool($enabled) || !is_array($raw) || count($raw)>500) { throw new ItaHttpError(422,'Phạm vi hoặc trạng thái truy cập không hợp lệ.'); }
        $scopes=array_values(array_unique(array_map([ItaInput::class,'positiveInt'],$raw)));sort($scopes);
        $permissions=ItaPermissions::validate($input['permissions'] ?? null);ItaPermissions::dependencies($permissions);
        if (array_diff($permissions,$this->actor->permissions)) { throw new ItaHttpError(403,'Không được cấp quyền mà chính bạn không được ủy quyền.'); }
        if ($enabled && $permissions && !in_array('scope.store',$permissions,true) && !$scopes) { throw new ItaHttpError(422,'Hãy chọn ít nhất một bộ phận phụ trách.'); }
        if ($scopes) {
            $marks=implode(',',array_fill(0,count($scopes),'?'));
            $count=$this->db->fetchOne("SELECT COUNT(*) n FROM ita_departments WHERE store_id=? AND status='active' AND id IN ($marks)",str_repeat('i',count($scopes)+1),[$this->actor->storeId,...$scopes]);
            if ((int)$count['n']!==count($scopes)) { throw new ItaHttpError(422,'Bộ phận không tồn tại/không hoạt động trong hệ thống.'); }
        }
        return [$id,$enabled,$scopes,$permissions];
    }

    private function checkActor(array $rows): void
    {
        $locked=false;foreach ($rows as $row) { if ((int)$row['user_id']===$this->actor->id && (int)$row['enabled']===1 && (int)$row['auth_revision']===$this->actor->revision) { $locked=true;break; } }
        $current=(new ItaUserObjects($this->db))->current($this->actor->storeId,$this->actor->id);
        if (!$locked || !$current || !$current->can('access.manage') || $current->permissions!==$this->actor->permissions) {
            throw new ItaHttpError(403,'Quyền quản trị đã thay đổi. Hãy đăng nhập lại.');
        }
    }

    private function lastManager(int $id,bool $enabled,array $permissions): void
    {
        $managers=$this->db->fetchAll("SELECT p.user_id FROM ita_user_permissions p JOIN ita_user_roles r ON r.store_id=p.store_id AND r.user_id=p.user_id WHERE p.store_id=? AND p.permission_key='access.manage' AND p.active=1 AND r.enabled=1 AND EXISTS (SELECT 1 FROM dc_users u WHERE u.store_id=p.store_id AND u.id=p.user_id AND u.status=1) ORDER BY p.user_id FOR UPDATE",'i',[$this->actor->storeId]);
        $ids=array_map(static fn($row)=>(int)$row['user_id'],$managers);
        if (in_array($id,$ids,true) && count($ids)===1 && (!$enabled || !in_array('access.manage',$permissions,true))) {
            throw new ItaHttpError(409,'Không thể thu hồi người quản lý quyền cuối cùng.');
        }
    }

    private function write(int $id,string $role,array $data): void
    {
        [$enabled,$scopes,$permissions]=$data;
        $this->db->execute('INSERT INTO ita_user_roles (store_id,user_id,role,enabled,updated_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE enabled=?,updated_by=?,auth_revision=auth_revision+1','iisiiii',[$this->actor->storeId,$id,$role,(int)$enabled,$this->actor->id,(int)$enabled,$this->actor->id]);
        $this->db->execute('UPDATE ita_user_scopes SET active=0 WHERE store_id=? AND user_id=?','ii',[$this->actor->storeId,$id]);
        foreach ($scopes as $scope) { $this->db->execute('INSERT INTO ita_user_scopes (store_id,user_id,department_id,active) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE active=1','iii',[$this->actor->storeId,$id,$scope]); }
        $this->db->execute('UPDATE ita_user_permissions SET active=0,updated_by=? WHERE store_id=? AND user_id=?','iii',[$this->actor->id,$this->actor->storeId,$id]);
        foreach ($permissions as $key) { $this->db->execute('INSERT INTO ita_user_permissions (store_id,user_id,permission_key,active,updated_by) VALUES (?,?,?,1,?) ON DUPLICATE KEY UPDATE active=1,updated_by=?','iisii',[$this->actor->storeId,$id,$key,$this->actor->id,$this->actor->id]); }
    }
}
