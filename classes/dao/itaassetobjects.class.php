<?php
class ItaAssetObjects
{
    private const SELECT = 'SELECT a.*,c.name category_name,d.name department_name FROM ita_assets a
        JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id
        LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id';

    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    private function scope(): array
    {
        $where = 'a.store_id=? AND a.is_deleted=0';
        $params = [$this->actor->storeId];
        if (!$this->actor->isAdmin()) {
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
        if (!$this->actor->isAdmin()) {
            $where .= $this->actor->departmentIds ? ' AND id IN ('.implode(',', array_fill(0, count($this->actor->departmentIds), '?')).')' : ' AND 1=0';
            $params = [...$params,...$this->actor->departmentIds];
        }
        $rows = $this->db->fetchAll('SELECT id,name FROM ita_departments WHERE '.$where.' ORDER BY name LIMIT 501', str_repeat('i', count($params)), $params);
        if (count($rows) > 500) {
            throw new ItaHttpError(422, 'Cần thu hẹp danh sách bộ phận.');
        }
        return array_map(fn($r)=>['value'=>(string) $r['id'],'label'=>$r['name']], $rows);
    }

    public function listing(array $filters, int $page): array
    {
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
        $status = ItaInput::text($filters['status'] ?? '', 20);
        if ($status !== '') {
            if (!in_array($status, ['in_use','maintenance','broken','retired'], true)) {
                throw new ItaHttpError(422, 'Trạng thái lọc không hợp lệ.');
            }
            $where .= ' AND a.status=?';
            $types .= 's';
            $params[] = $status;
        }
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_assets a WHERE '.$where, $types, $params)['n'];
        $page = min($page, max(1, (int) ceil($total/5)));
        $rows = $this->db->fetchAll(self::SELECT.' WHERE '.$where.' ORDER BY a.id DESC LIMIT 5 OFFSET ?', $types.'i', [...$params,($page-1)*5]);
        return ['assets'=>array_map(fn($r)=>(new ItaAssetObjectInfo($r))->presentation(), $rows),
            'pagination'=>['page'=>$page,'per_page'=>5,'total'=>$total]];
    }

    public function detail(int $id): array
    {
        $row = $this->db->fetchOne(self::SELECT.' WHERE a.store_id=? AND a.id=? AND a.is_deleted=0', 'ii', [$this->actor->storeId,$id]);
        if (!$row) {
            throw new ItaHttpError(404, 'Không tìm thấy thiết bị.');
        }
        $this->actor->requireDepartment($row['department_id'] === null ? null : (int) $row['department_id']);
        return (new ItaAssetObjectInfo($row))->presentation();
    }

    public function summary(): array
    {
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
        return ['total'=>array_sum($counts),'by_status'=>$counts,'by_department'=>$departments,'alerts'=>$alerts,
            'alerts_total'=>$counts['broken']+$counts['maintenance']];
    }
}
