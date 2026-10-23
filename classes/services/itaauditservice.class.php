<?php
class ItaAuditService
{
    private const FIELDS = ['asset_code','code','name','brand','model','serial_no','specs','os','purchase_date','purchase_price','warranty_end','vendor','location','category_id','department_id','current_user_id','status','is_deleted','status_reason','role','enabled','department_ids','total_rows',
        'asset_id','to_user_id','to_department_id','assigned_by','assigned_at','due_return','returned_at','handover_no','acknowledged_by','acknowledged_at','note','return_note',
        'software_name','license_type','total_seats','used_seats','start_date','expire_date','cost','allocation_id','user_id','override_reason',
        'technician_id','type','issue','handled_date','resolution_note','last_maintenance_at','next_maintenance_due','forecast_config','snapshot_hash','budget_summary','year','planned_headcount_increase','report_type','format','as_of',
        'source_asset_id','target_asset_id','link_type','source_port','target_port','capacity_label'];

    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    /** Credentials, license keys and session values are excluded by an explicit field allowlist. */
    public function record(string $action, string $entity, int $id, array $change = []): void
    {
        if (!in_array($action, ['auth.login','auth.logout','user.access_update','asset.create','asset.update','asset.soft_delete','asset.status_change','category.update','import.complete','assignment.assign','assignment.return','assignment.acknowledge','assignment.pdf_download',
            'license.create','license.update','license.allocate','license.release','license.overage','license.key_view',
            'ticket.create','ticket.assign','ticket.transition','ticket.cost','forecast.config','forecast.plan','forecast.export','report.export','network.save','network.delete'], true)) {
            throw new LogicException('Unknown ITA audit action');
        }
        $json = static function ($value): ?string {
            if ($value === null) {
                return null;
            }
            $safe = array_intersect_key($value, array_flip(self::FIELDS));
            return json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        };
        $this->db->execute('INSERT INTO ita_audit_logs (store_id,user_id,action,entity_type,entity_id,before_json,after_json)
            VALUES (?,?,?,?,?,?,?)', 'iississ', [$this->actor->storeId,$this->actor->id,$action,$entity,$id,
                $json($change['before'] ?? null),$json($change['after'] ?? null)]);
    }

    public function listing(array $filters, int $page): array
    {
        $this->actor->requireAdmin();
        $where = 'store_id=?';
        $types = 'i';
        $params = [$this->actor->storeId];
        if (($filters['action'] ?? '') !== '') {
            $where .= ' AND action=?';
            $types .= 's';
            $params[] = $filters['action'];
        }
        if (($filters['user_id'] ?? '') !== '') {
            $where .= ' AND user_id=?';
            $types .= 'i';
            $params[] = ItaInput::positiveInt($filters['user_id']);
        }
        foreach (['from'=>'>=','to'=>'<'] as $key=>$operator) {
            if (($filters[$key] ?? '') !== '') {
                $date = ItaInput::date($filters[$key]);
                $where .= ' AND created_at ' . $operator . ' ?';
                $types .= 's';
                $boundary = $key === 'to' ? $date->modify('+1 day') : $date;
                $params[] = $boundary->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }
        }
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_audit_logs WHERE '.$where, $types, $params)['n'];
        $page = min($page, max(1, (int) ceil($total/20)));
        $rows = $this->db->fetchAll('SELECT id,user_id,action,entity_type,entity_id,before_json,after_json,created_at
            FROM ita_audit_logs WHERE '.$where.' ORDER BY id DESC LIMIT 20 OFFSET ?', $types.'i', [...$params,($page-1)*20]);
        return ['rows'=>$rows,'page'=>$page,'total'=>$total,'per_page'=>20];
    }
}
