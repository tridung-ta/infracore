<?php
class ItaAssetService
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    /** Each write and its audit entry commit together; failures leave both unchanged. */
    public function save(array $input): int
    {
        $operation = ItaInput::text($input['operation'] ?? '', 20);
        if (!in_array($operation, ['create','update','status','note','delete'], true)) {
            throw new ItaHttpError(422, 'Thao tác tài sản không hợp lệ.');
        }
        if (in_array($operation, ['create','update'], true) && $this->actor->role === 'support_technician') {
            throw new ItaHttpError(403, 'Bạn chỉ được cập nhật trạng thái và ghi chú.');
        }
        if ($operation === 'delete') {
            $this->actor->requireAdmin();
        }
        $this->db->beginTransaction();
        try {
            $before = $operation === 'create' ? null : $this->lockAsset($input);
            if ($operation === 'create' || $operation === 'update') {
                $id = $this->saveFields($input, $before);
                $action = $operation === 'create' ? 'asset.create' : 'asset.update';
            } else {
                $id = (int) $before['id'];
                $action = $this->change($operation, $before, $input);
            }
            $after = $this->db->fetchOne('SELECT * FROM ita_assets WHERE store_id=? AND id=?', 'ii', [$this->actor->storeId,$id]);
            (new ItaAuditService($this->db,$this->actor))->record($action,'asset',$id,['before'=>$before,'after'=>$after]);
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof mysqli_sql_exception && (int) $error->getCode() === 1062) {
                throw new ItaHttpError(409, 'Mã tài sản đã tồn tại, kể cả trong tài sản đã xóa mềm.');
            }
            throw $error;
        }
    }

    private function lockAsset(array $input): array
    {
        $id = ItaInput::positiveInt($input['id'] ?? null);
        $row = $this->db->fetchOne('SELECT * FROM ita_assets WHERE store_id=? AND id=? AND is_deleted=0 FOR UPDATE', 'ii', [$this->actor->storeId,$id]);
        if (!$row) {
            throw new ItaHttpError(404, 'Không tìm thấy tài sản.');
        }
        $this->actor->requireDepartment($row['department_id'] === null ? null : (int) $row['department_id']);
        $version = ItaInput::text($input['version'] ?? '', 64);
        if (!hash_equals(self::version($row), $version)) {
            throw new ItaHttpError(409, 'Tài sản đã thay đổi. Hãy mở lại hồ sơ trước khi lưu.');
        }
        return $row;
    }

    public static function version(array $row): string
    {
        ksort($row);
        return hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
    }

    private function saveFields(array $input, ?array $before): int
    {
        $values = ItaAssetValidation::fields($input);
        $this->actor->requireDepartment($values['department_id']);
        $this->validateReferences($values,$before);
        if ($before && (int) $before['department_id'] !== (int) $values['department_id']) {
            $this->assertUnassigned((int) $before['id']);
        }
        $columns = array_keys($values);
        $types = str_repeat('s', count($values));
        if (!$before) {
            $this->db->execute('INSERT INTO ita_assets ('.implode(',',$columns).',store_id,created_by) VALUES ('.implode(',',array_fill(0,count($values)+2,'?')).')',
                $types.'ii', [...array_values($values),$this->actor->storeId,$this->actor->id]);
            return (int) $this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
        }
        $this->db->execute('UPDATE ita_assets SET '.implode(',',array_map(fn($c)=>$c.'=?',$columns)).',updated_by=? WHERE store_id=? AND id=?',
            $types.'iii', [...array_values($values),$this->actor->id,$this->actor->storeId,(int) $before['id']]);
        return (int) $before['id'];
    }

    private function validateReferences(array $values, ?array $before): void
    {
        $category = $this->db->fetchOne('SELECT id,status FROM ita_asset_categories WHERE store_id=? AND id=? FOR UPDATE', 'ii', [$this->actor->storeId,$values['category_id']]);
        $unchanged = $before && (int) $before['category_id'] === $values['category_id'];
        if (!$category || ($category['status'] !== 'active' && !$unchanged)) {
            throw new ItaHttpError(422, 'Loại tài sản không hoạt động hoặc không thuộc hệ thống.');
        }
        if ($values['department_id'] !== null) {
            $department = $this->db->fetchOne("SELECT id FROM ita_departments WHERE store_id=? AND id=? AND status='active' FOR UPDATE", 'ii', [$this->actor->storeId,$values['department_id']]);
            if (!$department) {
                throw new ItaHttpError(422, 'Bộ phận không hoạt động hoặc không thuộc hệ thống.');
            }
        }
    }

    private function change(string $operation, array $before, array $input): string
    {
        $id = (int) $before['id'];
        $reason = ItaInput::text($input['reason'] ?? '', 4000);
        if ($reason === '') {
            throw new ItaHttpError(422, 'Vui lòng nhập lý do hoặc ghi chú.');
        }
        if ($operation === 'delete') {
            $this->assertUnassigned($id);
            $this->db->execute('UPDATE ita_assets SET is_deleted=1,status_reason=?,updated_by=? WHERE store_id=? AND id=?', 'siii', [$reason,$this->actor->id,$this->actor->storeId,$id]);
            return 'asset.soft_delete';
        }
        if ($operation === 'note') {
            $this->db->execute('UPDATE ita_assets SET status_reason=?,updated_by=? WHERE store_id=? AND id=?', 'siii', [$reason,$this->actor->id,$this->actor->storeId,$id]);
            return 'asset.update';
        }
        $status = ItaInput::text($input['status'] ?? '', 20);
        ItaAssetValidation::status($before['status'],$status,$this->actor->role);
        if ($status === 'retired') {
            $this->assertUnassigned($id);
        }
        $this->db->execute('UPDATE ita_assets SET status=?,status_reason=?,updated_by=? WHERE store_id=? AND id=?', 'ssiii', [$status,$reason,$this->actor->id,$this->actor->storeId,$id]);
        return 'asset.status_change';
    }

    private function assertUnassigned(int $id): void
    {
        $asset = $this->db->fetchOne('SELECT current_user_id FROM ita_assets WHERE store_id=? AND id=?', 'ii', [$this->actor->storeId,$id]);
        $assignment = $this->db->fetchOne('SELECT id FROM ita_assignments WHERE store_id=? AND asset_id=? AND returned_at IS NULL LIMIT 1 FOR UPDATE', 'ii', [$this->actor->storeId,$id]);
        if ($asset['current_user_id'] !== null || $assignment) {
            throw new ItaHttpError(409, 'Cần thu hồi phân bổ trước khi chuyển bộ phận, thanh lý hoặc xóa mềm.');
        }
    }
}
