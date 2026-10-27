<?php
class ItaAssetObjects
{
    private const SELECT = 'SELECT a.*,c.name category_name,c.maintenance_cycle_months,d.name department_name FROM ita_assets a
        JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id
        LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id';

    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    private function scope(): array
    {
        $where = 'a.store_id=? AND a.is_deleted=0';
        $params = [$this->actor->storeId];
        if (!$this->actor->hasGlobalScope()) {
            if (!$this->actor->departmentIds) {
                $where .= ' AND 1=0';
            } else {
                $where .= ' AND a.department_id IN ('.implode(',', array_fill(0, count($this->actor->departmentIds), '?')).')';
                $params = [...$params,...$this->actor->departmentIds];
            }
        }
        return [$where, str_repeat('i', count($params)), $params];
    }

    public function departments(): array
    {
        $where = "store_id=? AND status='active'";
        $params = [$this->actor->storeId];
        if (!$this->actor->hasGlobalScope()) {
            $where .= $this->actor->departmentIds ? ' AND id IN ('.implode(',', array_fill(0, count($this->actor->departmentIds), '?')).')' : ' AND 1=0';
            $params = [...$params,...$this->actor->departmentIds];
        }
        $rows = $this->db->fetchAll('SELECT id,name FROM ita_departments WHERE '.$where.' ORDER BY name LIMIT 501', str_repeat('i', count($params)), $params);
        if (count($rows) > 500) {
            throw new ItaHttpError(422, 'Cần thu hẹp danh sách bộ phận.');
        }
        return array_map(fn($r)=>['value'=>(string) $r['id'],'label'=>$r['name']], $rows);
    }

    private function filteredScope(array $filters): array
    {
        ItaAssetValidation::warrantyRange($filters);
        [$where,$types,$params] = $this->scope();
        $query = ItaInput::text($filters['q'] ?? '', 100);
        if ($query !== '') {
            $pattern = '%'.str_replace(['=','%','_'], ['==','=%','=_'], $query).'%';
            $where .= " AND (a.asset_code LIKE ? ESCAPE '=' OR a.name LIKE ? ESCAPE '=' OR a.serial_no LIKE ? ESCAPE '=')";
            $types .= 'sss';
            $params = [...$params,$pattern,$pattern,$pattern];
        }
        if (($filters['department_id'] ?? '') !== '') {
            $where .= ' AND a.department_id=?';
            $types .= 'i';
            $params[] = ItaInput::positiveInt($filters['department_id']);
        }
        if (($filters['category_id'] ?? '') !== '') {
            $where .= ' AND a.category_id=?';
            $types .= 'i';
            $params[] = ItaInput::positiveInt($filters['category_id']);
        }
        if (($filters['purchase_year'] ?? '') !== '') {
            $year = ItaInput::text($filters['purchase_year'],4);
            if (!preg_match('/^[1-9][0-9]{3}$/D',$year) || (int) $year >= 9999) {
                throw new ItaHttpError(422,'Năm mua không hợp lệ.');
            }
            $where .= ' AND a.purchase_date>=? AND a.purchase_date<?';
            $types .= 'ss';
            $params = [...$params,$year.'-01-01',((int) $year+1).'-01-01'];
        }
        foreach (['warranty_from'=>'>=','warranty_to'=>'<='] as $field=>$operator) {
            if (($filters[$field] ?? '') !== '') {
                $where .= ' AND a.warranty_end '.$operator.' ?';
                $types .= 's';
                $params[] = ItaInput::date($filters[$field])->format('Y-m-d');
            }
        }
        $status = ItaInput::text($filters['status'] ?? '', 20);
        if ($status !== '') {
            if (!in_array($status, ['in_use','maintenance','broken','retired'], true)) {
                throw new ItaHttpError(422, 'Trạng thái lọc không hợp lệ.');
            }
            $where .= ' AND a.status=?';
            $types .= 's';
            $params[] = $status;
        }
        return [$where,$types,$params];
    }

    public function export(array $filters): array
    {
        if (!$this->actor->can('asset.export')) {
            throw new ItaHttpError(403,'Bạn không có quyền xuất tài sản.');
        }
        [$where,$types,$params] = $this->filteredScope($filters);
        $select = str_replace('c.name category_name,c.maintenance_cycle_months,d.name department_name','c.code category_code,d.code department_code',self::SELECT);
        $rows = $this->db->fetchAll($select.' WHERE '.$where.' ORDER BY a.id DESC LIMIT 5001',$types,$params);
        if (count($rows)>5000) {
            throw new ItaHttpError(422,'Có hơn 5.000 tài sản. Hãy thu hẹp bộ lọc trước khi xuất.');
        }
        return array_map(fn($row)=>array_map(fn($key)=>$row[$key] ?? '',ItaExcel::HEADERS),$rows);
    }

    public function listing(array $filters, int $page): array
    {
        if ($this->actor->capabilitiesEnabled) { $this->actor->requireCapability('asset.read'); }
        [$where,$types,$params] = $this->filteredScope($filters);
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_assets a WHERE '.$where, $types, $params)['n'];
        $page = min($page, max(1, (int) ceil($total/5)));
        $rows = $this->db->fetchAll(self::SELECT.' WHERE '.$where.' ORDER BY a.id DESC LIMIT 5 OFFSET ?', $types.'i', [...$params,($page-1)*5]);
        return ['assets'=>array_map(fn($r)=>(new ItaAssetObjectInfo($r))->presentation(), $rows),
            'pagination'=>['page'=>$page,'per_page'=>5,'total'=>$total]];
    }

    public function detail(int $id, int $page = 1): array
    {
        if ($this->actor->capabilitiesEnabled) { $this->actor->requireCapability('asset.read'); }
        $row = $this->db->fetchOne(self::SELECT.' WHERE a.store_id=? AND a.id=? AND a.is_deleted=0', 'ii', [$this->actor->storeId,$id]);
        if (!$row) {
            throw new ItaHttpError(404, 'Không tìm thấy thiết bị.');
        }
        $this->actor->requireDepartment($row['department_id'] === null ? null : (int) $row['department_id']);
        $raw = array_diff_key($row,array_flip(['category_name','department_name','maintenance_cycle_months']));
        $from = " FROM ita_audit_logs WHERE store_id=? AND entity_type='asset' AND entity_id=?";
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n'.$from,'ii',[$this->actor->storeId,$id])['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        $history = $this->db->fetchAll('SELECT id,user_id,action,before_json,after_json,created_at'.$from.' ORDER BY id DESC LIMIT 20 OFFSET ?','iii',[$this->actor->storeId,$id,($page-1)*20]);
        return (new ItaAssetObjectInfo($row))->presentation() + ['fields'=>$raw,'version'=>ItaAssetService::version($raw),
            'history'=>$history,'history_has_more'=>$page*20<$total,'history_pagination'=>['page'=>$page,'per_page'=>20,'total'=>$total]];
    }

    public function summary(): array
    {
        if ($this->actor->capabilitiesEnabled) { $this->actor->requireCapability('asset.read'); }
        [$where,$types,$params] = $this->scope();
        $counts = array_fill_keys(['in_use','maintenance','broken','retired'], 0);
        foreach ($this->db->fetchAll('SELECT a.status,COUNT(*) n FROM ita_assets a WHERE '.$where.' GROUP BY a.status', $types, $params) as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }
        $departments = $this->db->fetchAll("SELECT COALESCE(d.name,'Chưa phân bổ') department,COUNT(*) count FROM ita_assets a
            LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE $where GROUP BY a.department_id,d.name ORDER BY count DESC LIMIT 8", $types, $params);
        $alertRows = $this->db->fetchAll(self::SELECT." WHERE $where AND a.status IN ('broken','maintenance') ORDER BY FIELD(a.status,'broken','maintenance'),a.id DESC LIMIT 10", $types, $params);
        $alerts = array_map(fn($r)=>['asset_id'=>(int) $r['id'],'level'=>$r['status']==='broken'?'critical':'warning',
            'title'=>($r['status']==='broken'?'Thiết bị hỏng: ':'Đang bảo trì: ').$r['name'],
            'description'=>$r['asset_code'].' · '.($r['department_name'] ?? 'Chưa phân bổ')], $alertRows);
        $overdue = $this->maintenanceAlerts([$where,$types,$params]);
        $alerts = [...$alerts,...$overdue['alerts']];
        return ['total'=>array_sum($counts),'by_status'=>$counts,'by_department'=>$departments,'alerts'=>$alerts,
            'alerts_total'=>$counts['broken']+$counts['maintenance']+$overdue['total'],'maintenance_overdue_total'=>$overdue['total']];
    }

    private function maintenanceAlerts(array $scope): array
    {
        [$where,$types,$params] = $scope;
        $where .= " AND a.status<>'retired' AND ".ItaMaintenanceSchedule::SQL.'<?';
        $params[] = ItaAssignmentRules::today();
        $types .= 's';
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id WHERE '.$where,$types,$params)['n'];
        $rows = $this->db->fetchAll(self::SELECT.' WHERE '.$where.' ORDER BY a.next_maintenance_due,a.id LIMIT 10',$types,$params);
        return ['total'=>$total,'alerts'=>array_map(fn($row)=>['asset_id'=>(int) $row['id'],'level'=>'warning',
            'title'=>'Quá hạn bảo trì: '.$row['name'],'description'=>$row['asset_code'].' · Hạn '.ItaMaintenanceSchedule::effective($row)],$rows)];
    }
}
