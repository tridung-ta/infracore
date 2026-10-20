<?php
class ItaTicketObjects
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor) {}

    public function asset(int $id, bool $lock = false): array
    {
        return (new ItaAssignmentObjects($this->db,$this->actor))->asset($id,$lock);
    }

    public function row(int $id): array
    {
        $row = $this->db->fetchOne('SELECT * FROM ita_tickets WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
        if (!$row) {
            throw new ItaHttpError(404,'Không tìm thấy phiếu.');
        }
        $this->asset((int) $row['asset_id']);
        return ItaTicketObjectInfo::present($row);
    }

    private function scope(): array
    {
        $where = 'a.store_id=? AND a.is_deleted=0';
        $params = [$this->actor->storeId];
        if (!$this->actor->isAdmin()) {
            $ids = $this->actor->departmentIds;
            $where .= $ids ? ' AND a.department_id IN ('.implode(',',array_fill(0,count($ids),'?')).')' : ' AND 1=0';
            $params = [...$params,...$ids];
        }
        return [$where,str_repeat('i',count($params)),$params];
    }

    public function listing(array $input, int $page): array
    {
        [$where,$types,$params] = $this->scope();
        if (!empty($input['asset_id'])) {
            $id = ItaInput::positiveInt($input['asset_id']);
            $this->asset($id);
            $where .= ' AND t.asset_id=?';$types.='i';$params[]=$id;
        }
        $status = ItaInput::text($input['status'] ?? '',10);
        if ($status!=='') {
            if (!in_array($status,['pending','resolved','failed'],true)) {
                throw new ItaHttpError(422,'Trạng thái bộ lọc không hợp lệ.');
            }
            $where.=' AND t.status=?';$types.='s';$params[]=$status;
        }
        $from = ' FROM ita_tickets t JOIN ita_assets a ON a.store_id=t.store_id AND a.id=t.asset_id WHERE '.$where;
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n'.$from,$types,$params)['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        $rows = $this->db->fetchAll('SELECT t.id,t.ticket_no,t.asset_id,a.asset_code,t.type,t.status,t.severity,t.technician_id,t.opened_at,t.cost'.$from.' ORDER BY t.id DESC LIMIT 20 OFFSET ?',$types.'i',[...$params,($page-1)*20]);
        return ['rows'=>$rows,'page'=>$page,'total'=>$total,'per_page'=>20];
    }

    public function detail(int $id, int $page): array
    {
        $row = $this->row($id);
        $total = (int) $this->db->fetchOne("SELECT (SELECT COUNT(*) FROM ita_ticket_events WHERE store_id=? AND ticket_id=?) + (SELECT COUNT(*) FROM ita_audit_logs WHERE store_id=? AND entity_type='ticket' AND entity_id=? AND action IN ('ticket.assign','ticket.cost')) n",'iiii',[$this->actor->storeId,$id,$this->actor->storeId,$id])['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        $history = $this->db->fetchAll("SELECT * FROM (SELECT id,created_at,user_id,'status' kind,from_status,to_status,note,NULL before_json,NULL after_json FROM ita_ticket_events WHERE store_id=? AND ticket_id=? UNION ALL SELECT id,created_at,user_id,action kind,NULL,NULL,NULL,before_json,after_json FROM ita_audit_logs WHERE store_id=? AND entity_type='ticket' AND entity_id=? AND action IN ('ticket.assign','ticket.cost')) h ORDER BY created_at DESC,kind,id DESC LIMIT 20 OFFSET ?",'iiiii',[$this->actor->storeId,$id,$this->actor->storeId,$id,($page-1)*20]);
        $asset = $this->asset((int) $row['asset_id']);
        return ['ticket'=>$row,'asset'=>$asset+['version'=>ItaAssetService::version($asset),'awaiting_ready'=>ItaTicketGuard::waiting($this->db,$this->actor->storeId,$asset)],'history'=>$history,'page'=>$page,'total'=>$total,'per_page'=>20];
    }

    public function waitingAlerts(): array
    {
        [$where,$types,$params] = $this->scope();
        $where .= " AND a.status='maintenance' AND a.current_user_id IS NULL AND NOT EXISTS (SELECT 1 FROM ita_assignments x WHERE x.store_id=a.store_id AND x.asset_id=a.id AND x.returned_at IS NULL) AND NOT EXISTS (SELECT 1 FROM ita_tickets p WHERE p.store_id=a.store_id AND p.asset_id=a.id AND p.status='pending') AND (SELECT t.status FROM ita_tickets t WHERE t.store_id=a.store_id AND t.asset_id=a.id".ItaTicketGuard::LATEST.")='resolved'";
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_assets a WHERE '.$where,$types,$params)['n'];
        $rows = $this->db->fetchAll('SELECT a.id,a.asset_code,a.name FROM ita_assets a WHERE '.$where.' ORDER BY a.id LIMIT 10',$types,$params);
        return ['total'=>$total,'alerts'=>array_map(fn($r)=>['asset_id'=>(int) $r['id'],'level'=>'warning','title'=>'Chờ Admin xác nhận sẵn sàng: '.$r['name'],'description'=>$r['asset_code'].' · Phiếu đã resolved, thiết bị chưa phân bổ.'],$rows)];
    }

    public function technicians(int $assetId, int $page): array
    {
        if ($this->actor->role==='support_technician') {
            throw new ItaHttpError(403,'Bạn không có quyền gán kỹ thuật viên.');
        }
        $asset = $this->asset($assetId);
        $where = "u.store_id=? AND u.status=1 AND r.enabled=1 AND (r.role='admin_it' OR EXISTS (SELECT 1 FROM ita_user_scopes s WHERE s.store_id=u.store_id AND s.user_id=u.id AND s.active=1 AND s.department_id=?))";
        $from = ' FROM dc_users u JOIN ita_user_roles r ON r.store_id=u.store_id AND r.user_id=u.id WHERE '.$where;
        $params = [$this->actor->storeId,$asset['department_id']];
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n'.$from,'ii',$params)['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        return ['rows'=>$this->db->fetchAll('SELECT u.id,u.username,u.fullname'.$from.' ORDER BY u.id LIMIT 20 OFFSET ?','iii',[...$params,($page-1)*20]),'page'=>$page,'total'=>$total,'per_page'=>20];
    }

    public function statistics(array $input): array
    {
        $year = ItaInput::text($input['year'] ?? date('Y'),4);
        if (!preg_match('/^(20|21)[0-9]{2}$/D',$year)) {
            throw new ItaHttpError(422,'Năm thống kê phải trong 2000–2199.');
        }
        [$where,$types,$params] = $this->scope();
        if (!empty($input['asset_id'])) {
            $id=ItaInput::positiveInt($input['asset_id']);$this->asset($id);$where.=' AND t.asset_id=?';$types.='i';$params[]=$id;
        }
        if ($this->actor->role==='support_technician') {
            $where.=' AND t.technician_id=?';$types.='i';$params[]=$this->actor->id;
        }
        $start = new DateTimeImmutable($year.'-01-01',new DateTimeZone('Asia/Ho_Chi_Minh'));
        $params[]=$start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $params[]=$start->modify('+1 year')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        return ['year'=>(int) $year,'rows'=>$this->db->fetchAll('SELECT t.asset_id,a.asset_code,t.type,t.status,COUNT(*) n,COALESCE(SUM(t.cost),0) cost FROM ita_tickets t JOIN ita_assets a ON a.store_id=t.store_id AND a.id=t.asset_id WHERE '.$where.' AND t.opened_at>=? AND t.opened_at<? GROUP BY t.asset_id,a.asset_code,t.type,t.status',$types.'ss',$params)];
    }
}
