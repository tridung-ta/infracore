<?php
class ItaLicenseObjects
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor) {}

    public function row(int $id, bool $lock = false): array
    {
        ItaLicenseRules::manage($this->actor);
        $row = $this->db->fetchOne('SELECT * FROM ita_licenses WHERE store_id=? AND id=?'.($lock?' FOR UPDATE':''),'ii',[$this->actor->storeId,$id]);
        if (!$row) {
            throw new ItaHttpError(404,'Không tìm thấy giấy phép.');
        }
        $row['used_seats'] = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_license_allocations WHERE store_id=? AND license_id=? AND released_at IS NULL','ii',[$this->actor->storeId,$id])['n'];
        return $row;
    }

    public function listing(array $input, int $page): array
    {
        ItaLicenseRules::manage($this->actor);
        $query = ItaInput::text($input['q'] ?? '',100);
        $pattern = '%'.str_replace(['=','%','_'],['==','=%','=_'],$query).'%';
        $params = [$this->actor->storeId,$pattern];
        $where = "l.store_id=? AND l.software_name LIKE ? ESCAPE '='";
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_licenses l WHERE '.$where,'is',$params)['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        $rows = $this->db->fetchAll('SELECT l.*, (SELECT COUNT(*) FROM ita_license_allocations a WHERE a.store_id=l.store_id AND a.license_id=l.id AND a.released_at IS NULL) used_seats
            FROM ita_licenses l WHERE '.$where.' ORDER BY l.id DESC LIMIT 20 OFFSET ?','isi',[...$params,($page-1)*20]);
        return ['rows'=>array_map([ItaLicenseObjectInfo::class,'present'],$rows),'total'=>$total,'page'=>$page,'per_page'=>20];
    }

    public function detail(int $id, int $page): array
    {
        $row = $this->row($id);
        [$scope,$types,$params] = $this->allocationScope();
        $where = 'store_id=? AND license_id=?'.$scope;
        $params = [$this->actor->storeId,$id,...$params];
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_license_allocations WHERE '.$where,'ii'.$types,$params)['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        $rows = $this->db->fetchAll('SELECT id,asset_id,user_id,department_id,allocated_at,released_at FROM ita_license_allocations WHERE '.$where.' ORDER BY id DESC LIMIT 20 OFFSET ?','ii'.$types.'i',[...$params,($page-1)*20]);
        return ['license'=>ItaLicenseObjectInfo::present($row),'allocations'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>20];
    }

    public function alerts(): array
    {
        ItaLicenseRules::manage($this->actor);
        $today = new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh'));
        $source = '(SELECT l.id,l.software_name,l.expire_date,l.status,l.total_seats,(SELECT COUNT(*) FROM ita_license_allocations a WHERE a.store_id=l.store_id AND a.license_id=l.id AND a.released_at IS NULL) used_seats FROM ita_licenses l WHERE l.store_id=?) q';
        $level = "CASE WHEN used_seats>total_seats OR (status='active' AND expire_date<=?) THEN 'critical' ELSE 'warning' END";
        $where = "(used_seats>total_seats OR (status='active' AND expire_date<=?))";
        $params = [$today->modify('+7 days')->format('Y-m-d'),$this->actor->storeId,$today->modify('+30 days')->format('Y-m-d')];
        $counts = ['critical'=>0,'warning'=>0];
        foreach ($this->db->fetchAll('SELECT '.$level.' level,COUNT(*) n FROM '.$source.' WHERE '.$where.' GROUP BY level','sis',$params) as $row) {
            $counts[$row['level']] = (int) $row['n'];
        }
        $rows = $this->db->fetchAll('SELECT id,software_name,expire_date,status,total_seats,used_seats,'.$level.' level FROM '.$source.' WHERE '.$where.' ORDER BY level,expire_date,id LIMIT 20','sis',$params);
        $alerts = array_map(fn($row)=>['license_id'=>(int) $row['id'],'level'=>$row['level'],'title'=>'Giấy phép: '.$row['software_name'],
            'description'=>((int) $row['used_seats']>(int) $row['total_seats']?'Vượt số chỗ. ':'').($row['expire_date']&&$row['status']==='active'?'Hạn: '.$row['expire_date']:''),'url'=>'#licenses'],$rows);
        return ['alerts'=>$alerts,'counts'=>$counts,'total'=>array_sum($counts)];
    }

    private function allocationScope(): array
    {
        if ($this->actor->hasGlobalScope()) {
            return ['', '', []];
        }
        $ids = $this->actor->departmentIds;
        if (!$ids) {
            return [' AND 1=0','',[]];
        }
        $marks = implode(',',array_fill(0,count($ids),'?'));
        return [' AND (department_id IN ('.$marks.') OR asset_id IN (SELECT id FROM ita_assets WHERE store_id=? AND department_id IN ('.$marks.') AND is_deleted=0))',
            str_repeat('i',count($ids)*2+1),[...$ids,$this->actor->storeId,...$ids]];
    }
}
