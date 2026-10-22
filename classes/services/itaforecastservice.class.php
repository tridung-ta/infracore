<?php
class ItaForecastService
{
    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor) { ItaForecastRules::access($actor); }

    /** Shared lock order: actor, categories ascending, settings, department, plan. */
    private function lock(): void
    {
        $r=$this->db->fetchOne('SELECT role,enabled,auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=? FOR UPDATE','ii',[$this->actor->storeId,$this->actor->id]);
        if (!$r || !(int)$r['enabled'] || $r['role']!==$this->actor->role || (int)$r['auth_revision']!==$this->actor->revision) {
            throw new ItaHttpError(403,'Quyền đã thay đổi. Hãy đăng nhập lại.');
        }
        if (!$this->actor->isAdmin()) {
            $rows=$this->db->fetchAll('SELECT department_id FROM ita_user_scopes WHERE store_id=? AND user_id=? AND active=1 ORDER BY department_id FOR UPDATE','ii',[$this->actor->storeId,$this->actor->id]);
            $current=array_map('intval',array_column($rows,'department_id'));$known=$this->actor->departmentIds;sort($known);sort($current);
            if ($current!==$known) { throw new ItaHttpError(403,'Phạm vi đã thay đổi. Hãy tải lại.'); }
        }
        $rows=$this->db->fetchAll('SELECT id FROM ita_asset_categories WHERE store_id=? ORDER BY id LIMIT 501 FOR UPDATE','i',[$this->actor->storeId]);
        if (count($rows)>500) { throw new ItaHttpError(422,'Vượt giới hạn 500 loại thiết bị.'); }
        $this->db->fetchAll("SELECT setting_key FROM ita_settings WHERE store_id=? ORDER BY setting_key FOR UPDATE",'i',[$this->actor->storeId]);
    }

    private function transaction(callable $work): array
    {
        if (!in_array(strtoupper(str_replace('_','-',$this->db->transactionIsolation())),['REPEATABLE-READ','SERIALIZABLE'],true)) {
            throw new ItaHttpError(503,'Snapshot cần transaction REPEATABLE READ hoặc SERIALIZABLE; chưa lưu thay đổi.');
        }
        $this->db->beginTransaction();
        try { $this->lock();$result=$work();$this->db->commit();return $result; }
        catch (Throwable $e) {
            $this->db->rollBack();
            if ($e instanceof mysqli_sql_exception && in_array((int)$e->getCode(),[1062,1205,1213],true)) {
                throw new ItaHttpError(409,'Có thao tác đồng thời. Tải lại trước khi lưu; không tự gửi lại.');
            }
            throw $e;
        }
    }

    public function configure(array $input): array
    {
        $this->actor->requireAdmin();
        return $this->transaction(function() use ($input) {
            $dao=new ItaForecastObjects($this->db,$this->actor);$before=$dao->configuration();
            if (!hash_equals($before['version'],ItaInput::text($input['version'] ?? '',64))) { throw new ItaHttpError(409,'Cấu hình đã đổi. Hãy tải lại.'); }
            $id=ItaInput::positiveInt($input['category_id'] ?? null);$map=array_column($before['categories'],null,'id');
            if (!isset($map[$id])) { throw new ItaHttpError(404,'Không tìm thấy loại phần cứng.'); }
            $price=$input['price'] ?? null;
            if ($price==='') { $price=null; }
            if ($price!==null && (!is_string($price) || !preg_match('/^(0|[1-9][0-9]{0,15})(\.[0-9]{1,2})?$/D',$price))) { throw new ItaHttpError(422,'Giá phải là số tiền VND không âm, tối đa 2 số lẻ.'); }
            $units=ItaForecastRules::parameter($input['units_per_person'] ?? null,1000);
            $percent=ItaForecastRules::parameter($input['maintenance_percent'] ?? null,100);
            $values=[];
            foreach ($map as $cid=>$c) { $values[(string)$cid]=['units_per_person'=>$c['units_per_person'],'maintenance_percent'=>$c['maintenance_percent']]; }
            $values[(string)$id]=['units_per_person'=>$units,'maintenance_percent'=>$percent];
            $json=json_encode($values,JSON_THROW_ON_ERROR);
            $this->db->execute('UPDATE ita_asset_categories SET default_replacement_cost=?,updated_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','sii',[$price,$this->actor->storeId,$id]);
            $this->db->execute("INSERT INTO ita_settings (store_id,setting_key,setting_value,updated_by) VALUES (?,'forecast_category_parameters',?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by=VALUES(updated_by),updated_at=UTC_TIMESTAMP()",'isi',[$this->actor->storeId,$json,$this->actor->id]);
            (new ItaAuditService($this->db,$this->actor))->record('forecast.config','category',$id,['before'=>['forecast_config'=>$map[$id]],'after'=>['forecast_config'=>['price'=>$price,'units_per_person'=>$units,'maintenance_percent'=>$percent]]]);
            return $dao->configuration();
        });
    }

    public function save(array $input): array
    {
        $year=ItaForecastRules::year($input['year'] ?? null);$department=ItaInput::positiveInt($input['department_id'] ?? null);
        $this->actor->requireDepartment($department);
        $n=$input['planned_headcount_increase'] ?? null;
        if ((!is_int($n) && !is_string($n)) || !preg_match('/^(0|[1-9][0-9]{0,5})$/D',(string)$n)) { throw new ItaHttpError(422,'Nhân sự tăng phải là số nguyên từ 0 đến 999999.'); }
        $note=ItaInput::text($input['note'] ?? '',2000);
        return $this->transaction(function() use ($input,$year,$department,$n,$note) {
            $d=$this->db->fetchOne('SELECT id FROM ita_departments WHERE store_id=? AND id=? FOR UPDATE','ii',[$this->actor->storeId,$department]);
            if (!$d) { throw new ItaHttpError(404,'Không tìm thấy bộ phận.'); }
            $before=$this->db->fetchOne('SELECT * FROM ita_budget_plans WHERE store_id=? AND year=? AND department_id=? FOR UPDATE','iii',[$this->actor->storeId,$year,$department]);
            if ($before && $before['status']!=='draft') { throw new ItaHttpError(409,'Kế hoạch đã chốt, chỉ được xem snapshot.'); }
            $version=ItaInput::text($input['version'] ?? '',64);
            if (($before && (!hash_equals(ItaForecastObjectInfo::version($before),$version) || ($input['confirm_recalculate'] ?? false)!==true)) || (!$before && $version!=='')) {
                throw new ItaHttpError(409,'Kế hoạch đã đổi hoặc chưa xác nhận tính lại. Hãy tải lại.');
            }
            $snapshot=(new ItaForecastObjects($this->db,$this->actor))->calculate(['year'=>$year,'department_id'=>$department],(int)$n);
            // Snapshot contains only this department's rows and calculation inputs, not global catalog/config.
            unset($snapshot['config'],$snapshot['plans']);
            $json=json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            if (strlen($json)>2000000) { throw new ItaHttpError(422,'Snapshot vượt giới hạn 2 MB.'); }
            if ($before) {
                $id=(int)$before['id'];
                $this->db->execute('UPDATE ita_budget_plans SET planned_headcount_increase=?,note=?,calculation_snapshot_json=?,updated_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','issii',[(int)$n,$note,$json,$this->actor->storeId,$id]);
            } else {
                $this->db->execute('INSERT INTO ita_budget_plans (store_id,year,department_id,planned_headcount_increase,note,calculation_snapshot_json,created_by) VALUES (?,?,?,?,?,?,?)','iiiissi',[$this->actor->storeId,$year,$department,(int)$n,$note,$json,$this->actor->id]);
                $id=(int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
            }
            (new ItaAuditService($this->db,$this->actor))->record('forecast.plan','budget_plan',$id,['before'=>['snapshot_hash'=>$before?hash('sha256',$before['calculation_snapshot_json'] ?? ''):null],
                'after'=>['year'=>$year,'department_id'=>$department,'planned_headcount_increase'=>(int)$n,'note'=>$note,'budget_summary'=>$snapshot['summary'],'snapshot_hash'=>hash('sha256',$json)]]);
            return (new ItaForecastObjects($this->db,$this->actor))->saved($id);
        });
    }

    public function auditExport(array $document): void
    {
        $this->transaction(function() use ($document) {
            if (!$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->actor->storeId,$this->actor->id])) {
                throw new ItaHttpError(403,'Tài khoản đã ngừng hoạt động. Không thể xuất dữ liệu.');
            }
            foreach ($document['rows'] as $row) { $this->actor->requireDepartment((int)$row['department_id']); }
            if (!empty($document['saved'])) {
                $current=(new ItaForecastObjects($this->db,$this->actor))->saved((int)$document['plan_id']);
                if (!hash_equals($current['version'],$document['version'])) { throw new ItaHttpError(409,'Kế hoạch đã thay đổi. Hãy xuất lại.'); }
            }
            (new ItaAuditService($this->db,$this->actor))->record('forecast.export','budget_plan',(int)($document['plan_id'] ?? 0),
                ['after'=>['year'=>$document['year'],'budget_summary'=>$document['summary'],'snapshot_hash'=>hash('sha256',json_encode($document['rows'],JSON_THROW_ON_ERROR))]]);
            return [];
        });
    }
}
