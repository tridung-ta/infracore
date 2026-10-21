<?php
/** Bounded, scoped read model. No SQL writes; snapshot owned by the controller/service. */
class ItaDashboardObjects
{
    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor,private string $asOf) {}

    public function queryPlans(array $input): array
    {
        $where='a.store_id=? AND a.is_deleted=0';$params=[$this->actor->storeId];
        if (!$this->actor->isAdmin()) {
            $ids=$this->actor->departmentIds;
            $where.=$ids?' AND a.department_id IN ('.implode(',',array_fill(0,count($ids),'?')).')':' AND 1=0';$params=[...$params,...$ids];
        }
        foreach (['department_id','category_id'] as $key) {
            if (($input[$key] ?? '')!=='') {
                $id=ItaInput::positiveInt($input[$key]);if ($key==='department_id') { $this->actor->requireDepartment($id); }
                $where.=' AND a.'.$key.'=?';$params[]=$id;
            }
        }
        $types=str_repeat('i',count($params));
        $assets='SELECT a.id,a.asset_code,a.name,a.category_id,a.department_id,a.status,a.purchase_date,a.warranty_end,c.name category_name,d.name department_name,'.ItaMaintenanceSchedule::SQL.' maintenance_due,
            (a.status=\'maintenance\' AND a.current_user_id IS NULL AND NOT EXISTS (SELECT 1 FROM ita_assignments x WHERE x.store_id=a.store_id AND x.asset_id=a.id AND x.returned_at IS NULL) AND NOT EXISTS (SELECT 1 FROM ita_tickets p WHERE p.store_id=a.store_id AND p.asset_id=a.id AND p.status=\'pending\') AND (SELECT t.status FROM ita_tickets t WHERE t.store_id=a.store_id AND t.asset_id=a.id'.ItaTicketGuard::LATEST.')=\'resolved\') awaiting_ready
            FROM ita_assets a LEFT JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE '.$where.' ORDER BY a.id LIMIT 5001';
        return ['assets'=>['sql'=>$assets,'types'=>$types,'params'=>$params]];
    }

    public function assets(array $input): array
    {
        $p=$this->queryPlans($input)['assets'];$rows=$this->db->fetchAll($p['sql'],$p['types'],$p['params']);
        if (count($rows)>5000) { throw new ItaHttpError(422,'Phạm vi vượt 5.000 tài sản. Hãy lọc bộ phận/loại; không cắt ngầm.'); }
        return $rows;
    }

    public function licensePlan(array $input): array
    {
        $params=[$this->actor->storeId];$scope='';$ids=$this->actor->isAdmin()?[]:$this->actor->departmentIds;
        if (($input['department_id'] ?? '')!=='') { $ids=[ItaInput::positiveInt($input['department_id'])];$this->actor->requireDepartment($ids[0]); }
        if (!$this->actor->isAdmin() || $ids) {
            $marks=implode(',',array_fill(0,count($ids),'?'));
            $scope=$ids?' AND EXISTS (SELECT 1 FROM ita_license_allocations x LEFT JOIN ita_assets a ON a.store_id=x.store_id AND a.id=x.asset_id AND a.is_deleted=0 WHERE x.store_id=l.store_id AND x.license_id=l.id AND x.released_at IS NULL AND (x.department_id IN ('.$marks.') OR a.department_id IN ('.$marks.')))':' AND 1=0';
            $params=[...$params,...$ids,...$ids];
        }
        // Category-filtered views must not mix unrelated shared license alerts.
        if (($input['category_id'] ?? '')!=='') { $scope.=' AND 1=0'; }
        return ['sql'=>'SELECT l.id,l.software_name,l.status,l.expire_date,l.total_seats,(SELECT COUNT(*) FROM ita_license_allocations x WHERE x.store_id=l.store_id AND x.license_id=l.id AND x.released_at IS NULL) used_seats FROM ita_licenses l WHERE l.store_id=?'.$scope.' ORDER BY l.id LIMIT 5001','types'=>str_repeat('i',count($params)),'params'=>$params];
    }

    public function licenses(array $input): array
    {
        $p=$this->licensePlan($input);$rows=$this->db->fetchAll($p['sql'],$p['types'],$p['params']);
        if (count($rows)>5000) { throw new ItaHttpError(422,'Vượt 5.000 giấy phép trong phạm vi cảnh báo.'); }
        return $rows;
    }

    public function dataset(array $input,bool $maintenanceOnly=false): array
    {
        ItaDashboardRules::access($this->actor,$maintenanceOnly);$assets=$this->assets($input);
        $today=substr($this->asOf,0,10);$scores=[];$config=null;
        if (!$maintenanceOnly) {
            $life=new ItaLifecycleObjects($this->db,$this->actor,$today);$plan=$life->queryPlans($input)['report'];
            $rows=$this->db->fetchAll($plan['sql'],$plan['types'],$plan['params']);
            if (count($rows)>5000) { throw new ItaHttpError(422,'Vượt phạm vi phân tích 5.000 thiết bị.'); }
            $config=$life->configuration();
            foreach ($rows as $r) { $scores[(int)$r['id']]=ItaLifecycleObjectInfo::present($r,$config['values']); }
        }
        return ['assets'=>$assets,'scores'=>$scores,'config'=>$config,'as_of'=>$this->asOf];
    }

    public function dashboard(array $input): array
    {
        $d=$this->dataset($input);$group=ItaInput::text($input['group'] ?? 'category',20);
        $groups=ItaDashboardRules::groups($d['assets'],$d['scores'],$group);$analyzed=count($d['scores']);
        if (count($groups)>500) { throw new ItaHttpError(422,'Biểu đồ vượt 500 nhóm. Hãy thu hẹp bộ phận/loại.'); }
        $failed=count(array_filter($d['scores'],fn($s)=>(int)$s['repair_count']>0));
        $alerts=ItaDashboardRules::alerts($d['assets'],$this->licenses($input),substr($this->asOf,0,10));
        return ['as_of'=>$this->asOf,'config'=>$d['config'],'groups'=>$groups,'group'=>$group,'alerts_total'=>count($alerts),
            'summary'=>['total'=>count($d['assets']),'managed'=>count(array_filter($d['assets'],fn($a)=>$a['status']!=='retired')),'analyzed'=>$analyzed,'failed_assets'=>$failed,'fault_rate'=>$analyzed?$failed/$analyzed:null,'replace'=>count(array_filter($d['scores'],fn($s)=>$s['tier']==='replace'))],
            'replacements'=>array_values(array_map(fn($s)=>array_intersect_key($s,array_flip(['id','asset_code','name','score_raw','score_display','tier'])),array_filter($d['scores'],fn($s)=>$s['tier']==='replace'))),
            'departments'=>(new ItaAssetObjects($this->db,$this->actor))->departments()];
    }

    public function alerts(array $input,int $page): array
    {
        ItaDashboardRules::access($this->actor);$all=ItaDashboardRules::alerts($this->assets($input),$this->licenses($input),substr($this->asOf,0,10));
        $level=ItaInput::text($input['level'] ?? '',10);$reason=ItaInput::text($input['reason'] ?? '',30);$q=mb_strtolower(ItaInput::text($input['q'] ?? '',100),'UTF-8');
        if (!in_array($level,['','critical','warning'],true)) { throw new ItaHttpError(422,'Mức độ không hợp lệ.'); }
        $rows=array_values(array_filter($all,fn($a)=>($level===''||$level===$a['level'])&&($reason===''||$reason===$a['reason'])&&($q===''||str_contains(mb_strtolower($a['code'].' '.$a['name'],'UTF-8'),$q))));
        return ItaDashboardRules::paginate($rows,$page)+['as_of'=>$this->asOf,'active_total'=>count($all)];
    }
}
