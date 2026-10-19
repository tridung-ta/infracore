<?php
class ItaAssignmentService
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    /** Asset lock, unique active assignment and audit protect one handover transaction. */
    public function save(array $input): array
    {
        $operation = ItaInput::text($input['operation'] ?? '',20);
        if (!in_array($operation,['assign','return','acknowledge'],true)) {
            throw new ItaHttpError(422,'Thao tác bàn giao không hợp lệ.');
        }
        $id = ItaInput::positiveInt($input['asset_id'] ?? null);
        $this->db->beginTransaction();
        try {
            $this->lockActor();
            $asset = (new ItaAssignmentObjects($this->db,$this->actor))->asset($id,true);
            $this->version($asset,$input['asset_version'] ?? null);
            $active = $this->db->fetchOne('SELECT * FROM ita_assignments WHERE store_id=? AND asset_id=? AND returned_at IS NULL LIMIT 1 FOR UPDATE','ii',[$this->actor->storeId,$id]);
            if ($operation==='assign') {
                $assignmentId = $this->assign($asset,$active,$input);
            } else {
                $this->activeVersion($active,$input);
                $assignmentId = (int) $active['id'];
                $operation==='return' ? $this->receive($asset,$active,$input) : $this->acknowledge($active);
            }
            $after = $this->db->fetchOne('SELECT * FROM ita_assignments WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$assignmentId]);
            if ($operation==='return') {
                $after['return_note'] = ItaInput::text($input['note'],4000);
            }
            (new ItaAuditService($this->db,$this->actor))->record('assignment.'.$operation,'assignment',$assignmentId,['before'=>$active,'after'=>$after]);
            $this->db->commit();
            return ['id'=>$assignmentId,'asset_id'=>$id,'operation'=>$operation];
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof mysqli_sql_exception && in_array((int) $error->getCode(),[1062,1213,1205],true)) {
                throw new ItaHttpError(409,'Bàn giao vừa thay đổi hoặc đang có thao tác đồng thời. Hãy tải lại để kiểm tra.');
            }
            throw $error;
        }
    }

    private function lockActor(): void
    {
        $row = $this->db->fetchOne('SELECT role,enabled,auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=? FOR UPDATE','ii',[$this->actor->storeId,$this->actor->id]);
        if (!$row || !(int) $row['enabled'] || $row['role']!==$this->actor->role || (int) $row['auth_revision']!==$this->actor->revision) {
            throw new ItaHttpError(403,'Quyền đã thay đổi. Vui lòng đăng nhập lại.');
        }
    }

    private function version(array $row, mixed $version): void
    {
        if (!hash_equals(ItaAssetService::version($row),ItaInput::text($version ?? '',64))) {
            throw new ItaHttpError(409,'Dữ liệu đã thay đổi. Hãy tải lại hồ sơ trước khi thao tác.');
        }
    }

    private function activeVersion(?array $active, array $input): void
    {
        if (!$active || (int) $active['id']!==ItaInput::positiveInt($input['assignment_id'] ?? null)) {
            throw new ItaHttpError(409,'Không còn lượt bàn giao đang mở này.');
        }
        $this->version($active,$input['assignment_version'] ?? null);
    }

    private function assign(array $asset, ?array $active, array $input): int
    {
        ItaAssignmentRules::assignable($asset,$active,$this->actor);
        $target = $this->target($asset,$input);
        $due = ItaAssignmentRules::dueDate($input['due_return'] ?? '');
        $note = ItaInput::text($input['note'] ?? '',4000);
        $number = 'ITA-'.str_replace('-','',ItaAssignmentRules::today()).'-'.strtoupper(bin2hex(random_bytes(8)));
        $this->db->execute('INSERT INTO ita_assignments (store_id,asset_id,to_user_id,to_department_id,assigned_by,due_return,handover_no,note)
            VALUES (?,?,?,?,?,?,?,?)','iiiiisss',[$this->actor->storeId,$asset['id'],$target['user_id'],$target['to_department_id'],$this->actor->id,$due,$number,$note]);
        $id = (int) $this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
        $this->db->execute('UPDATE ita_assets SET current_user_id=?,department_id=?,updated_by=? WHERE store_id=? AND id=?','iiiii',
            [$target['user_id'],$target['asset_department_id'],$this->actor->id,$this->actor->storeId,$asset['id']]);
        (new ItaAuditService($this->db,$this->actor))->record('asset.update','asset',(int) $asset['id'],
            ['before'=>['current_user_id'=>$asset['current_user_id'],'department_id'=>$asset['department_id']],
                'after'=>['current_user_id'=>$target['user_id'],'department_id'=>$target['asset_department_id']]]);
        return $id;
    }

    private function target(array $asset, array $input): array
    {
        $type = ItaInput::text($input['target_type'] ?? '',20);
        $id = ItaInput::positiveInt($input['target_id'] ?? null);
        $department = $asset['department_id']===null ? null : (int) $asset['department_id'];
        if ($type==='department') {
            $department = $id;
        } elseif ($type==='user') {
            if (!$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->actor->storeId,$id])) {
                throw new ItaHttpError(422,'Người nhận không hoạt động hoặc không thuộc hệ thống.');
            }
        } else {
            throw new ItaHttpError(422,'Hình thức nhận bàn giao không hợp lệ.');
        }
        $this->actor->requireDepartment($department);
        if ($department!==null && !$this->db->fetchOne("SELECT id FROM ita_departments WHERE store_id=? AND id=? AND status='active' FOR UPDATE",'ii',[$this->actor->storeId,$department])) {
            throw new ItaHttpError(422,'Bộ phận nhận không hoạt động hoặc không thuộc hệ thống.');
        }
        return ['user_id'=>$type==='user'?$id:null,'to_department_id'=>$type==='department'?$id:null,'asset_department_id'=>$department];
    }

    private function receive(array $asset, array $active, array $input): void
    {
        $reason = ItaInput::text($input['note'] ?? '',4000);
        if ($reason==='') {
            throw new ItaHttpError(422,'Vui lòng ghi nhận lý do/tình trạng khi thu hồi.');
        }
        $this->db->execute('UPDATE ita_assignments SET returned_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$active['id']]);
        $this->db->execute('UPDATE ita_assets SET current_user_id=NULL,updated_by=? WHERE store_id=? AND id=?','iii',[$this->actor->id,$this->actor->storeId,$asset['id']]);
        (new ItaAuditService($this->db,$this->actor))->record('asset.update','asset',(int) $asset['id'],
            ['before'=>['current_user_id'=>$asset['current_user_id']],'after'=>['current_user_id'=>null]]);
    }

    private function acknowledge(array $active): void
    {
        ItaAssignmentRules::acknowledge($active,$this->actor);
        $this->db->execute('UPDATE ita_assignments SET acknowledged_by=?,acknowledged_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','iii',[$this->actor->id,$this->actor->storeId,$active['id']]);
    }
}
