<?php
class ItaLicenseService
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor) {}

    /** Locks the actor/license before any change; audit failure rolls back every ITA write. */
    public function save(array $input): array
    {
        ItaLicenseRules::manage($this->actor);
        $operation = ItaInput::text($input['operation'] ?? 'save',20);
        if (!in_array($operation,['save','allocate','release'],true)) {
            throw new ItaHttpError(422,'Thao tác giấy phép không hợp lệ.');
        }
        $id = empty($input['id']) ? 0 : ItaInput::positiveInt($input['id']);
        if (!$id && $operation!=='save') {
            throw new ItaHttpError(422,'Thiếu giấy phép.');
        }
        $this->db->beginTransaction();
        try {
            $this->lockActor();
            $row = $id ? (new ItaLicenseObjects($this->db,$this->actor))->row($id,true) : null;
            if ($row && !hash_equals(ItaLicenseObjectInfo::present($row)['version'],ItaInput::text($input['version'] ?? '',64))) {
                throw new ItaHttpError(409,'Giấy phép đã thay đổi. Hãy tải lại.');
            }
            $result = $operation==='save' ? $this->metadata($row,$input) : $this->allocation($row,$operation,$input);
            $this->db->commit();
            return ['id'=>$result];
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof mysqli_sql_exception && in_array((int) $error->getCode(),[1062,1213,1205],true)) {
                throw new ItaHttpError(409,'Cấp phát bị trùng hoặc có thao tác đồng thời. Hãy tải lại.');
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

    private function metadata(?array $before, array $input): int
    {
        // Licenses are shared store-wide: no department ownership column exists.
        $this->actor->requireAdmin();
        $fields = ItaLicenseRules::fields($input);
        if ($before && $before['license_type']!==$fields['license_type']) {
            $history = $this->db->fetchOne('SELECT COUNT(*) n FROM ita_license_allocations WHERE store_id=? AND license_id=?','ii',[$this->actor->storeId,$before['id']]);
            if ((int) $history['n']>0) {
                throw new ItaHttpError(422,'Giấy phép có lịch sử cấp phát không được đổi hình thức.');
            }
        }
        $reason = ItaLicenseRules::excess($this->actor,(int) ($before['used_seats'] ?? 0),$fields['total_seats'],$input);
        $key = $before['license_key_enc'] ?? null;
        $plain = ItaInput::text($input['license_key'] ?? '',4000);
        if ($plain!=='') {
            $key = ItaLicenseCrypto::configured()->encrypt($plain,'ita-license-store:'.$this->actor->storeId);
        }
        $values = [$fields['software_name'],$fields['vendor'],$key,$fields['license_type'],$fields['total_seats'],$fields['start_date'],$fields['expire_date'],$fields['cost'],$fields['status']];
        if ($before) {
            $id = (int) $before['id'];
            $this->db->execute('UPDATE ita_licenses SET software_name=?,vendor=?,license_key_enc=?,license_type=?,total_seats=?,start_date=?,expire_date=?,cost=?,status=?,updated_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','ssssissssii',[...$values,$this->actor->storeId,$id]);
        } else {
            $this->db->execute('INSERT INTO ita_licenses (software_name,vendor,license_key_enc,license_type,total_seats,start_date,expire_date,cost,status,store_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)','ssssissssii',[...$values,$this->actor->storeId,$this->actor->id]);
            $id = (int) $this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
        }
        $this->audit($before?'license.update':'license.create',$id,$before,$fields);
        if ($reason!==null) {
            $this->audit('license.overage',$id,null,['override_reason'=>$reason,'total_seats'=>$fields['total_seats'],'used_seats'=>$before['used_seats']]);
        }
        return $id;
    }

    private function allocation(array $row, string $operation, array $input): int
    {
        if ($operation==='release') {
            $id = ItaInput::positiveInt($input['allocation_id'] ?? null);
            $allocation = $this->db->fetchOne('SELECT * FROM ita_license_allocations WHERE store_id=? AND license_id=? AND id=? FOR UPDATE','iii',[$this->actor->storeId,$row['id'],$id]);
            if (!$allocation || $allocation['released_at']!==null) {
                throw new ItaHttpError(409,'Lượt cấp phát không còn hiệu lực.');
            }
            $this->targetScope($allocation,true);
            $this->db->execute('UPDATE ita_license_allocations SET released_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
            $this->audit('license.release',(int) $row['id'],null,['allocation_id'=>$id]);
            return (int) $row['id'];
        }
        $alerts = ItaLicenseRules::alert($row['expire_date'],0,1);
        $today = (new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        if ($row['status']!=='active' || ($alerts['days_remaining']!==null && $alerts['days_remaining']<0) || ($row['start_date']!==null && $row['start_date']>$today)) {
            throw new ItaHttpError(422,'Giấy phép chưa có hiệu lực, đã ngừng sử dụng hoặc hết hạn.');
        }
        $field = ['per_device'=>'asset_id','per_user'=>'user_id','per_department'=>'department_id'][$row['license_type']];
        $target = ['asset_id'=>null,'user_id'=>null,'department_id'=>null];
        $target[$field] = ItaInput::positiveInt($input['target_id'] ?? null);
        $this->targetScope($target);
        $reason = ItaLicenseRules::excess($this->actor,(int) $row['used_seats']+1,(int) $row['total_seats'],$input);
        $this->db->execute('INSERT INTO ita_license_allocations (store_id,license_id,asset_id,user_id,department_id,allocated_by) VALUES (?,?,?,?,?,?)','iiiiii',[$this->actor->storeId,$row['id'],$target['asset_id'],$target['user_id'],$target['department_id'],$this->actor->id]);
        $this->audit('license.allocate',(int) $row['id'],null,$target);
        if ($reason!==null) {
            $this->audit('license.overage',(int) $row['id'],null,['override_reason'=>$reason,'used_seats'=>(int) $row['used_seats']+1,'total_seats'=>(int) $row['total_seats']]);
        }
        return (int) $row['id'];
    }

    private function targetScope(array $target, bool $releasing = false): void
    {
        if ($target['asset_id']!==null) {
            $asset = $this->db->fetchOne('SELECT department_id,status,is_deleted FROM ita_assets WHERE store_id=? AND id=? FOR UPDATE','ii',[$this->actor->storeId,$target['asset_id']]);
            if (!$asset) {
                throw new ItaHttpError(404,'Không tìm thấy thiết bị.');
            }
            $this->actor->requireDepartment($asset['department_id']===null?null:(int) $asset['department_id']);
            if (!$releasing && ((int) $asset['is_deleted'] || $asset['status']==='retired')) {
                throw new ItaHttpError(422,'Thiết bị không còn sử dụng được.');
            }
        } elseif ($target['department_id']!==null) {
            $this->actor->requireDepartment((int) $target['department_id']);
            if (!$this->db->fetchOne('SELECT id FROM ita_departments WHERE store_id=? AND id=?'.($releasing?'':" AND status='active'").' FOR UPDATE','ii',[$this->actor->storeId,$target['department_id']])) {
                throw new ItaHttpError(422,'Bộ phận không hoạt động.');
            }
        } else {
            // Native users have no verified membership relation; never infer it from store_id.
            $this->actor->requireAdmin();
            if (!$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=?'.($releasing?'':' AND status=1'),'ii',[$this->actor->storeId,$target['user_id']])) {
                throw new ItaHttpError(422,'Tài khoản không hoạt động hoặc ngoài hệ thống.');
            }
        }
    }

    private function audit(string $action, int $id, ?array $before, array $after): void
    {
        (new ItaAuditService($this->db,$this->actor))->record($action,'license',$id,['before'=>$before,'after'=>$after]);
    }
}
