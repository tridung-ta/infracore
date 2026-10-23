<?php
class ItaNetworkService
{
    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor) { ItaNetworkRules::access($actor); }

    private function actorLock(): void
    {
        $r=$this->db->fetchOne('SELECT role,enabled,auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=? FOR UPDATE','ii',[$this->actor->storeId,$this->actor->id]);
        if (!$r || !(int)$r['enabled'] || $r['role']!=='admin_it' || (int)$r['auth_revision']!==$this->actor->revision) { throw new ItaHttpError(403,'Quyền đã thay đổi. Hãy tải lại.'); }
        $native=$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->actor->storeId,$this->actor->id]);
        if (!$native) { throw new ItaHttpError(403,'Tài khoản không còn hoạt động.'); }
    }

    public function read(callable $work): array
    {
        $this->isolation();$this->db->beginTransaction();
        try { $result=$work();$this->db->commit();return $result; }
        catch (Throwable $e) { $this->db->rollBack();throw $e; }
    }

    private function isolation(): void
    {
        if (!in_array(strtoupper(str_replace('_','-',$this->db->transactionIsolation())),['REPEATABLE-READ','SERIALIZABLE'],true)) { throw new ItaHttpError(503,'Sơ đồ cần kết nối REPEATABLE READ hoặc SERIALIZABLE.'); }
    }

    /** Lock actor then all old/new endpoints ascending before locking the link. */
    public function save(array $input): array
    {
        $this->actor->requireAdmin();$action=ItaInput::text($input['action'] ?? 'save',10);
        if (!in_array($action,['save','delete'],true)) { throw new ItaHttpError(422,'Thao tác không hợp lệ.'); }
        $id=($input['id'] ?? '')===''?null:ItaInput::positiveInt($input['id']);
        if ($action==='delete' && !$id) { throw new ItaHttpError(422,'Thiếu liên kết cần ngừng sử dụng.'); }
        $values=$action==='save'?ItaNetworkRules::values($input):[];
        $version=ItaInput::text($input['version'] ?? '',64);
        $this->isolation();$this->db->beginTransaction();
        try {
            $this->actorLock();
            $old=$id?$this->db->fetchOne('SELECT * FROM ita_network_links WHERE store_id=? AND id=? AND is_deleted=0','ii',[$this->actor->storeId,$id]):null;
            if ($id && !$old) { throw new ItaHttpError(404,'Không tìm thấy liên kết.'); }
            $ids=array_unique([...($old?[(int)$old['source_asset_id'],(int)$old['target_asset_id']]:[]),...($values?[$values['source_asset_id'],$values['target_asset_id']]:[])]);sort($ids,SORT_NUMERIC);
            foreach ($ids as $assetId) {
                $asset=$this->db->fetchOne('SELECT id,category_id,is_deleted FROM ita_assets WHERE store_id=? AND id=? FOR UPDATE','ii',[$this->actor->storeId,$assetId]);
                if (!$asset) { throw new ItaHttpError(404,'Không tìm thấy thiết bị.'); }
                $category=$this->db->fetchOne('SELECT is_software FROM ita_asset_categories WHERE store_id=? AND id=?','ii',[$this->actor->storeId,(int)$asset['category_id']]);
                if ($action==='save' && (in_array($assetId,[$values['source_asset_id'],$values['target_asset_id']],true)) && ((int)$asset['is_deleted'] || !$category || (int)$category['is_software'])) { throw new ItaHttpError(422,'Liên kết chỉ dùng thiết bị phần cứng chưa xóa.'); }
            }
            $before=$id?$this->db->fetchOne('SELECT * FROM ita_network_links WHERE store_id=? AND id=? AND is_deleted=0 FOR UPDATE','ii',[$this->actor->storeId,$id]):null;
            if (($id && (!$before || !hash_equals(ItaNetworkObjectInfo::version($before),$version) || ItaNetworkObjectInfo::version($old)!==ItaNetworkObjectInfo::version($before))) || (!$id && $version!=='')) { throw new ItaHttpError(409,'Liên kết đã đổi. Hãy tải lại trước khi lưu.'); }
            if ($action==='delete') {
                $this->db->execute('UPDATE ita_network_links SET is_deleted=1,updated_at=UTC_TIMESTAMP(6) WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
                $after=['is_deleted'=>1];
            } else { $id=$this->write($id,$values);$after=$values+['is_deleted'=>0]; }
            (new ItaAuditService($this->db,$this->actor))->record('network.'.$action,'network_link',$id,['before'=>$before,'after'=>$after]);
            $this->db->commit();return ['id'=>$id];
        } catch (Throwable $e) {
            $this->db->rollBack();
            if ($e instanceof mysqli_sql_exception && in_array((int)$e->getCode(),[1062,1205,1213],true)) { throw new ItaHttpError(409,'Có thay đổi đồng thời hoặc liên kết trùng. Tải lại, không tự gửi lại.'); }
            throw $e;
        }
    }

    private function write(?int $id,array $v): int
    {
        $duplicate=$this->db->fetchOne('SELECT id FROM ita_network_links WHERE store_id=? AND source_asset_id=? AND target_asset_id=? AND is_deleted=0 FOR UPDATE','iii',[$this->actor->storeId,$v['source_asset_id'],$v['target_asset_id']]);
        if ($duplicate && (int)$duplicate['id']!==$id) { throw new ItaHttpError(409,'Hai thiết bị đã có liên kết.'); }
        $params=[$v['source_asset_id'],$v['target_asset_id'],$v['link_type'],$v['source_port'],$v['target_port'],$v['capacity_label'],$v['note']];
        if ($id) {
            $this->db->execute('UPDATE ita_network_links SET source_asset_id=?,target_asset_id=?,link_type=?,source_port=?,target_port=?,capacity_label=?,note=?,updated_at=UTC_TIMESTAMP(6) WHERE store_id=? AND id=?','iisssssii',[...$params,$this->actor->storeId,$id]);
        } else {
            $this->db->execute('INSERT INTO ita_network_links (source_asset_id,target_asset_id,link_type,source_port,target_port,capacity_label,note,store_id) VALUES (?,?,?,?,?,?,?,?)','iisssssi',[...$params,$this->actor->storeId]);
            $id=(int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
        }
        return $id;
    }
}
