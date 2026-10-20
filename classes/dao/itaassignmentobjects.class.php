<?php
class ItaAssignmentObjects
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    public function asset(int $id, bool $locking = false): array
    {
        $row = $this->db->fetchOne('SELECT * FROM ita_assets WHERE store_id=? AND id=? AND is_deleted=0'.($locking?' FOR UPDATE':''),'ii',[$this->actor->storeId,$id]);
        if (!$row) {
            throw new ItaHttpError(404,'Không tìm thấy thiết bị.');
        }
        $this->actor->requireDepartment($row['department_id']===null ? null : (int) $row['department_id']);
        return $row;
    }

    public function detail(int $id, int $page): array
    {
        $asset = $this->asset($id);
        $active = $this->db->fetchOne('SELECT * FROM ita_assignments WHERE store_id=? AND asset_id=? AND returned_at IS NULL LIMIT 1','ii',[$this->actor->storeId,$id]);
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_assignments WHERE store_id=? AND asset_id=?','ii',[$this->actor->storeId,$id])['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        $rows = $this->db->fetchAll('SELECT id,to_user_id,to_department_id,assigned_by,assigned_at,due_return,returned_at,handover_no,acknowledged_by,acknowledged_at,note
            FROM ita_assignments WHERE store_id=? AND asset_id=? ORDER BY assigned_at DESC,id DESC LIMIT 20 OFFSET ?','iii',[$this->actor->storeId,$id,($page-1)*20]);
        return ['asset'=>['id'=>$id,'code'=>$asset['asset_code'],'name'=>$asset['name'],'status'=>$asset['status'],
            'department_id'=>$asset['department_id'],'current_user_id'=>$asset['current_user_id'],'version'=>ItaAssetService::version($asset),
            'maintenance_overdue'=>ItaAssignmentRules::overdue($asset),'next_maintenance_due'=>$asset['next_maintenance_due'] ?? null],
            'active'=>$active ? $this->presentation($active) : null,'history'=>$rows,'page'=>$page,'per_page'=>20,'total'=>$total,
            'pdf_available'=>ItaHandoverPdf::available()];
    }

    private function presentation(array $row): array
    {
        $version = ItaAssetService::version($row);
        unset($row['handover_file']);
        return $row+['version'=>$version];
    }

    public function recipients(string $query, int $page): array
    {
        if (!in_array($this->actor->role,['admin_it','system_manager'],true)) {
            throw new ItaHttpError(403,'Bạn không có quyền chọn người nhận bàn giao mới.');
        }
        $pattern = '%'.str_replace(['=','%','_'],['==','=%','=_'],$query).'%';
        $where = "store_id=? AND status=1 AND (username LIKE ? ESCAPE '=' OR fullname LIKE ? ESCAPE '=')";
        $params = [$this->actor->storeId,$pattern,$pattern];
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM dc_users WHERE '.$where,'iss',$params)['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        $rows = $this->db->fetchAll('SELECT id,username,fullname FROM dc_users WHERE '.$where.' ORDER BY id LIMIT 20 OFFSET ?','issi',[...$params,($page-1)*20]);
        return ['rows'=>$rows,'page'=>$page,'per_page'=>20,'total'=>$total];
    }
}
