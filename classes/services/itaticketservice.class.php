<?php
class ItaTicketService
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor) {}

    public function save(array $input): array
    {
        if ($this->actor->capabilitiesEnabled) { $permission=match($input['operation'] ?? ''){'create'=>'ticket.create','assign'=>'ticket.assign','transition'=>'ticket.process','cost'=>'ticket.cost','ready'=>'ticket.ready',default=>throw new ItaHttpError(422,'Thao tác không hợp lệ.')};$this->actor->requireCapability($permission); }
        $operation = ItaInput::text($input['operation'] ?? '',12);
        if (!in_array($operation,['create','assign','transition','cost','ready'],true)) {
            throw new ItaHttpError(422,'Thao tác phiếu không hợp lệ.');
        }
        $id = $operation==='create'||$operation==='ready' ? 0 : ItaInput::positiveInt($input['id'] ?? null);
        // Initial read discovers the asset only. All mutation reads are repeated under its lock.
        $initial = $id ? (new ItaTicketObjects($this->db,$this->actor))->row($id) : null;
        $assetId = $initial ? (int) $initial['asset_id'] : ItaInput::positiveInt($input['asset_id'] ?? null);
        $this->db->beginTransaction();
        try {
            $this->lockActor();
            $asset = (new ItaTicketObjects($this->db,$this->actor))->asset($assetId,true);
            if ($asset['status']==='retired' && $operation!=='cost') {
                throw new ItaHttpError(409,'Thiết bị đã thanh lý, không thể xử lý phiếu.');
            }
            if ($operation==='ready') {
                $this->ready($asset,$input);$result=['asset_id'=>$assetId];
            } elseif ($operation==='create') {
                $result=['id'=>$this->create($asset,$input),'asset_id'=>$assetId];
            } else {
                $ticket = $this->db->fetchOne('SELECT * FROM ita_tickets WHERE store_id=? AND id=? AND asset_id=? FOR UPDATE','iii',[$this->actor->storeId,$id,$assetId]);
                if (!$ticket || !hash_equals(ItaAssetService::version($ticket),ItaInput::text($input['version'] ?? '',64))) {
                    throw new ItaHttpError(409,'Phiếu đã thay đổi. Hãy tải lại trước khi lưu.');
                }
                if ($operation!=='cost' && $this->db->fetchOne("SELECT id FROM ita_tickets WHERE store_id=? AND asset_id=? AND status='pending' AND id<>? LIMIT 1 FOR UPDATE",'iii',[$this->actor->storeId,$assetId,$id])) {
                    throw new ItaHttpError(409,'Thiết bị đã có phiếu pending khác. Hãy đối chiếu các phiếu trước khi xử lý hoặc mở lại.');
                }
                $this->change($operation,$ticket,$asset,$input);$result=['id'=>$id,'asset_id'=>$assetId];
            }
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof mysqli_sql_exception && in_array((int) $error->getCode(),[1062,1205,1213],true)) {
                throw new ItaHttpError(409,'Có thao tác đồng thời. Hãy tải lại phiếu để kiểm tra.');
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

    private function create(array $asset, array $input): int
    {
        ItaTicketGuard::clear($this->db,$this->actor->storeId,(int) $asset['id']);
        $type = ItaInput::text($input['type'] ?? '',10);
        $severity = ItaInput::text($input['severity'] ?? '',10);
        if (!in_array($type,['repair','periodic'],true) || !in_array($severity,['low','medium','high','critical'],true)) {
            throw new ItaHttpError(422,'Loại phiếu hoặc mức độ không hợp lệ.');
        }
        $issue = ItaTicketRules::note($input);
        $number = 'IT-'.str_replace('-','',ItaTicketRules::today()).'-'.bin2hex(random_bytes(8));
        $this->db->execute("INSERT INTO ita_tickets (store_id,ticket_no,asset_id,type,reported_by,issue,severity,status) VALUES (?,?,?,?,?,?,?,'pending')",'isisiss',[$this->actor->storeId,$number,$asset['id'],$type,$this->actor->id,$issue,$severity]);
        $id = (int) $this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
        $this->event($id,null,'pending',$issue);
        $this->assetStatus($asset,'maintenance');
        $this->audit('ticket.create',$id,null,['asset_id'=>$asset['id'],'type'=>$type,'status'=>'pending','issue'=>$issue]);
        return $id;
    }

    private function change(string $operation, array $ticket, array $asset, array $input): void
    {
        $id = (int) $ticket['id'];
        $note = ItaTicketRules::note($input);
        if ($operation==='assign') {
            $this->assign($ticket,$asset,$input,$note);
            return;
        }
        if ($operation==='cost') {
            $this->actor->requireCapability('ticket.cost');
            if (!in_array($ticket['status'],['resolved','failed'],true)) {
                throw new ItaHttpError(422,'Chỉ sửa chi phí đã đóng bằng thao tác này.');
            }
            $cost = ItaTicketRules::cost($input['cost'] ?? '');
            $this->db->execute('UPDATE ita_tickets SET cost=?,updated_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','sii',[$cost,$this->actor->storeId,$id]);
            $this->audit('ticket.cost',$id,['cost'=>$ticket['cost']],['cost'=>$cost,'note'=>$note]);
            return;
        }
        if (!$this->actor->can('ticket.process')) {
            throw new ItaHttpError(403,'Quản lý hệ thống chỉ tạo phiếu, xem và gán kỹ thuật viên.');
        }
        $to = ItaInput::text($input['status'] ?? '',10);
        ItaTicketRules::transition($ticket['status'],$to);
        if ($to==='pending') {
            ItaTicketGuard::clear($this->db,$this->actor->storeId,(int) $asset['id']);
            $this->db->execute("UPDATE ita_tickets SET status='pending',resolved_at=NULL,updated_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?",'ii',[$this->actor->storeId,$id]);
            $this->assetStatus($asset,'maintenance');
        } else {
            $this->close($ticket,$asset,$input,$to,$note);
        }
        $this->event($id,$ticket['status'],$to,$note);
        $this->audit('ticket.transition',$id,['status'=>$ticket['status'],'cost'=>$ticket['cost']],['status'=>$to,'cost'=>$input['cost'] ?? $ticket['cost'],'note'=>$note]);
    }

    private function assign(array $ticket, array $asset, array $input, string $note): void
    {
        if (!$this->actor->can('ticket.assign')) {
            throw new ItaHttpError(403,'Bạn không có quyền gán kỹ thuật viên.');
        }
        if ($ticket['status']!=='pending') {
            throw new ItaHttpError(422,'Chỉ gán kỹ thuật viên cho phiếu pending.');
        }
        $id = ItaInput::positiveInt($input['technician_id'] ?? null);
        $technician = (new ItaUserObjects($this->db))->current($this->actor->storeId,$id);
        if (!$technician) {
            throw new ItaHttpError(422,'Kỹ thuật viên không hoạt động hoặc chưa có quyền ITA.');
        }
        if ($this->actor->capabilitiesEnabled && !$technician->can('ticket.process')) {
            throw new ItaHttpError(422,'Tài khoản chưa có quyền xử lý phiếu.');
        }
        $technician->requireDepartment($asset['department_id']===null?null:(int) $asset['department_id']);
        $this->db->execute('UPDATE ita_tickets SET technician_id=?,updated_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','iii',[$id,$this->actor->storeId,$ticket['id']]);
        $this->audit('ticket.assign',(int) $ticket['id'],['technician_id'=>$ticket['technician_id']],['technician_id'=>$id,'note'=>$note]);
    }

    private function close(array $ticket, array $asset, array $input, string $to, string $note): void
    {
        $handled = ItaTicketRules::handled($input,$ticket);
        $cost = ItaTicketRules::cost($input['cost'] ?? '');
        if (!empty($ticket['handled_date']) && !ItaTicketRules::sameCost($cost,(string) $ticket['cost'])) {
            $this->actor->requireCapability('ticket.cost');
        }
        $this->db->execute('UPDATE ita_tickets SET status=?,handled_date=?,resolved_at=UTC_TIMESTAMP(),cost=?,resolution_note=?,updated_at=UTC_TIMESTAMP() WHERE store_id=? AND id=?','ssssii',[$to,$handled,$cost,$note,$this->actor->storeId,$ticket['id']]);
        $assigned = $asset['current_user_id']!==null || $this->db->fetchOne('SELECT id FROM ita_assignments WHERE store_id=? AND asset_id=? AND returned_at IS NULL LIMIT 1','ii',[$this->actor->storeId,$asset['id']]);
        $this->assetStatus($asset,$to==='failed'?'broken':($assigned?'in_use':'maintenance'));
        if ($to==='resolved' && $ticket['type']==='periodic') {
            $category = $this->db->fetchOne('SELECT maintenance_cycle_months FROM ita_asset_categories WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$asset['category_id']]);
            $due = ItaMaintenanceSchedule::next($handled,isset($category['maintenance_cycle_months'])?(int) $category['maintenance_cycle_months']:null);
            $last = (new DateTimeImmutable($handled,new DateTimeZone('Asia/Ho_Chi_Minh')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $this->db->execute('UPDATE ita_assets SET last_maintenance_at=?,next_maintenance_due=?,updated_by=? WHERE store_id=? AND id=?','ssiii',[$last,$due,$this->actor->id,$this->actor->storeId,$asset['id']]);
            (new ItaAuditService($this->db,$this->actor))->record('asset.update','asset',(int) $asset['id'],['before'=>['last_maintenance_at'=>$asset['last_maintenance_at'] ?? null,'next_maintenance_due'=>$asset['next_maintenance_due'] ?? null],'after'=>['last_maintenance_at'=>$last,'next_maintenance_due'=>$due]]);
        }
    }

    private function ready(array $asset, array $input): void
    {
        $this->actor->requireCapability('ticket.ready');
        if (!hash_equals(ItaAssetService::version($asset),ItaInput::text($input['asset_version'] ?? '',64))) {
            throw new ItaHttpError(409,'Thiết bị đã thay đổi. Hãy tải lại.');
        }
        ItaTicketGuard::clear($this->db,$this->actor->storeId,(int) $asset['id']);
        if (!ItaTicketGuard::waiting($this->db,$this->actor->storeId,$asset)) {
            throw new ItaHttpError(409,'Thiết bị không ở trạng thái chờ xác nhận sẵn sàng.');
        }
        ItaTicketRules::note($input);
        $this->assetStatus($asset,'in_use');
        (new ItaAuditService($this->db,$this->actor))->record('asset.update','asset',(int) $asset['id'],['after'=>['note'=>$input['note']]]);
    }

    private function assetStatus(array $asset, string $status): void
    {
        $this->db->execute('UPDATE ita_assets SET status=?,updated_by=? WHERE store_id=? AND id=?','siii',[$status,$this->actor->id,$this->actor->storeId,$asset['id']]);
        (new ItaAuditService($this->db,$this->actor))->record('asset.status_change','asset',(int) $asset['id'],['before'=>['status'=>$asset['status']],'after'=>['status'=>$status]]);
    }

    private function event(int $id, ?string $from, string $to, string $note): void
    {
        if ($from!==null) {
            ItaTicketRules::transition($from,$to);
        } elseif ($to!=='pending') {
            throw new LogicException('Invalid initial ticket state');
        }
        $this->db->execute('INSERT INTO ita_ticket_events (store_id,ticket_id,from_status,to_status,user_id,note) VALUES (?,?,?,?,?,?)','iissis',[$this->actor->storeId,$id,$from,$to,$this->actor->id,$note]);
    }

    private function audit(string $action, int $id, ?array $before, array $after): void
    {
        (new ItaAuditService($this->db,$this->actor))->record($action,'ticket',$id,['before'=>$before,'after'=>$after]);
    }
}
