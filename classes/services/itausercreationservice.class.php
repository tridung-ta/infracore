<?php
require_once __DIR__.'/itausercreationinput.class.php';
/** New native rows only; no deletion or credential reset of existing accounts. */
class ItaUserCreationService
{
    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor) {}

    public function create(array $input): array
    {
        $this->actor->requireCapability('access.manage');
        if (!$this->actor->capabilitiesEnabled) { throw new ItaHttpError(503,'Cần hoàn tất kích hoạt quyền chức năng trước khi tạo tài khoản mới.'); }
        $this->actor->requireCapability('scope.store');
        $data=ItaUserCreationInput::validate($input,$this->actor);$data['store_id']=$this->actor->storeId;
        $fields=ItaUserCreationInput::nativeFields($this->db->fetchAll('SHOW COLUMNS FROM dc_users'),$data);
        unset($data['password']);$id=0;
        $this->db->beginTransaction();
        try {
            $this->lockAndCheck($data);
            $names=array_keys($fields);$types=implode('',array_map(static fn($v)=>is_int($v)?'i':'s',array_values($fields)));
            $this->db->execute('INSERT INTO dc_users (`'.implode('`,`',$names).'`) VALUES ('.implode(',',array_fill(0,count($names),'?')).')',$types,array_values($fields));
            $id=(int)($this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'] ?? 0);
            if ($id<=0) { throw new RuntimeException('Native account ID unavailable'); }
            $this->writeAccess($id,$data);
            (new ItaAuditService($this->db,$this->actor))->record('user.create','user',$id,
                ['after'=>['name'=>$data['fullname'],'enabled'=>true,'department_ids'=>$data['department_ids'],'permissions'=>$data['permissions']]]);
            $this->checkActor($data['permissions']);
            $this->db->execute('UPDATE dc_users SET status=1 WHERE store_id=? AND id=? AND status=0','ii',[$this->actor->storeId,$id]);
            $native=$this->db->fetchOne('SELECT id,status FROM dc_users WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
            if (!$native || (int)$native['status']!==1) { throw new RuntimeException('Native account activation failed'); }
            $this->db->commit();
            return ['id'=>$id,'username'=>$data['username']];
        } catch (Throwable $error) {
            // MyISAM survives ROLLBACK; compensate ONLY the newly inserted ID.
            if ($id>0) {
                try { $this->db->execute('UPDATE dc_users SET status=0 WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]); }
                catch (Throwable) { error_log('ITA account provisioning requires operator review.'); }
            }
            $this->db->rollBack();
            if ($id>0) { throw new ItaHttpError(503,'Chưa hoàn tất tạo tài khoản #'.$id.'. Không tạo lại; kiểm trạng thái tài khoản trước khi tiếp tục.'); }
            throw $error;
        }
    }

    private function lockAndCheck(array $data): void
    {
        $this->db->fetchAll('SELECT user_id,enabled,auth_revision FROM ita_user_roles WHERE store_id=? ORDER BY user_id FOR UPDATE','i',[$this->actor->storeId]);
        $this->checkActor($data['permissions']);
        $duplicates=$this->db->fetchAll('SELECT id FROM dc_users WHERE store_id=? AND (username IN (?,?) OR email IN (?,?)) LIMIT 2','issss',
            [$this->actor->storeId,$data['username'],$data['email'],$data['username'],$data['email']]);
        if ($duplicates) { throw new ItaHttpError(409,'Tên đăng nhập hoặc email đã tồn tại. Hãy kiểm danh sách tài khoản.'); }
        if ($data['department_ids']) {
            $ids=$data['department_ids'];$marks=implode(',',array_fill(0,count($ids),'?'));
            $rows=$this->db->fetchAll("SELECT id FROM ita_departments WHERE store_id=? AND status='active' AND id IN ($marks) ORDER BY id FOR UPDATE",str_repeat('i',count($ids)+1),[$this->actor->storeId,...$ids]);
            if (count($rows)!==count($ids)) { throw new ItaHttpError(422,'Bộ phận không còn hoạt động hoặc không thuộc hệ thống.'); }
        }
    }

    private function checkActor(array $permissions): void
    {
        $current=(new ItaUserObjects($this->db))->current($this->actor->storeId,$this->actor->id);
        if (!$current || $current->revision!==$this->actor->revision || !$current->can('access.manage') || !$current->can('scope.store')
            || array_diff($permissions,$current->permissions)) { throw new ItaHttpError(403,'Quyền quản trị đã thay đổi. Hãy đăng nhập lại.'); }
    }

    private function writeAccess(int $id,array $data): void
    {
        // Metadata role is the least-privileged legacy preset, not native Admin.
        $this->db->execute("INSERT INTO ita_user_roles (store_id,user_id,role,enabled,updated_by) VALUES (?,?,'support_technician',1,?)",'iii',[$this->actor->storeId,$id,$this->actor->id]);
        foreach ($data['department_ids'] as $scope) { $this->db->execute('INSERT INTO ita_user_scopes (store_id,user_id,department_id,active) VALUES (?,?,?,1)','iii',[$this->actor->storeId,$id,$scope]); }
        foreach ($data['permissions'] as $key) { $this->db->execute('INSERT INTO ita_user_permissions (store_id,user_id,permission_key,active,updated_by) VALUES (?,?,?,1,?)','iisi',[$this->actor->storeId,$id,$key,$this->actor->id]); }
    }
}
