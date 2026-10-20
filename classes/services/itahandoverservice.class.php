<?php
class ItaHandoverService
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    public function document(int $id): array
    {
        $row = $this->db->fetchOne('SELECT * FROM ita_assignments WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
        if (!$row) throw new ItaHttpError(404,'Không tìm thấy lượt bàn giao.');
        $asset = (new ItaAssignmentObjects($this->db,$this->actor))->asset((int) $row['asset_id']);
        $recipient = $row['to_user_id']!==null ? $this->person((int) $row['to_user_id']) : $this->department((int) $row['to_department_id']);
        $fields = [['label'=>'Bên giao','value'=>$this->person((int) $row['assigned_by'])],['label'=>'Bên nhận','value'=>$recipient],
            ['label'=>'Ngày bàn giao','value'=>self::date($row['assigned_at'])],['label'=>'Mã thiết bị','value'=>$asset['asset_code']],
            ['label'=>'Tên thiết bị','value'=>$asset['name']],['label'=>'Số seri','value'=>$asset['serial_no'] ?? 'Chưa ghi nhận'],
            ['label'=>'Hạn thu hồi','value'=>$row['due_return'] ? (new DateTimeImmutable($row['due_return']))->format('d/m/Y') : 'Chưa đặt'],
            ['label'=>'Thu hồi','value'=>$row['returned_at'] ? self::date($row['returned_at']) : 'Chưa thu hồi'],
            ['label'=>'Xác nhận đã nhận','value'=>$row['acknowledged_at'] ? $this->person((int) $row['acknowledged_by']).' - '.self::date($row['acknowledged_at']) : 'Chưa có xác nhận điện tử']];
        $audit = $row['returned_at'] ? $this->db->fetchOne("SELECT after_json FROM ita_audit_logs WHERE store_id=? AND entity_type='assignment' AND entity_id=? AND action='assignment.return' ORDER BY id DESC LIMIT 1",'ii',[$this->actor->storeId,$id]) : null;
        $change = json_decode($audit['after_json'] ?? 'null',true);
        $returnNote = is_array($change) && is_string($change['return_note'] ?? null) ? $change['return_note'] : '';
        return ['number'=>ItaInput::text($row['handover_no'],64),'fields'=>$fields,'source_version'=>hash('sha256',ItaAssetService::version($row).ItaAssetService::version($asset)),
            'note'=>ItaInput::text($row['note'] ?? '',4000) ?: 'Không có ghi chú.','return_note'=>ItaInput::text($returnNote,4000)];
    }

    /** Rendering must succeed and the download be audited before returning any bytes. */
    public function download(int $id): string
    {
        $this->currentActor();
        $document = $this->document($id);
        $bytes = ItaHandoverPdf::render($document);
        // Recheck scope after the potentially slow render; never emit unauthorized bytes.
        $current = $this->currentActor();
        $latest = (new self($this->db,$current))->document($id);
        if (!hash_equals($document['source_version'],$latest['source_version'])) {
            throw new ItaHttpError(409,'Hồ sơ bàn giao đã thay đổi trong lúc tạo PDF. Hãy tải lại.');
        }
        (new ItaAuditService($this->db,$this->actor))->record('assignment.pdf_download','assignment',$id,
            ['after'=>['handover_no'=>$document['number']]]);
        return $bytes;
    }

    private function currentActor(): ItaUserObjectInfo
    {
        $current = (new ItaUserObjects($this->db))->current($this->actor->storeId,$this->actor->id);
        if (!$current || $current->revision!==$this->actor->revision || $current->role!==$this->actor->role) {
            throw new ItaHttpError(403,'Quyền đã thay đổi. Vui lòng đăng nhập lại.');
        }
        return $current;
    }

    private function person(int $id): string
    {
        $row = $this->db->fetchOne('SELECT username,fullname FROM dc_users WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
        return $row ? ItaInput::text($row['fullname'] ?: $row['username'],500).' (#'.$id.')' : 'Tài khoản #'.$id.' (không còn trong danh bạ)';
    }

    private function department(int $id): string
    {
        $row = $this->db->fetchOne('SELECT name FROM ita_departments WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
        return $row ? 'Bộ phận: '.ItaInput::text($row['name'],150) : 'Bộ phận #'.$id.' (không còn trong danh mục)';
    }

    private static function date(string $value): string
    {
        return (new DateTimeImmutable($value,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y H:i');
    }
}
