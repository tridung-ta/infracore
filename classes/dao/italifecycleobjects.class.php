<?php
/** Read-only lifecycle queries. Each repair ticket contributes once, never per event. */
class ItaLifecycleObjects
{
    private array $config;
    private array $window;

    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor,?string $today=null)
    {
        if (!$actor->can('lifecycle.read') && !($actor->capabilitiesEnabled && ($actor->can('forecast.read') || $actor->can('report.read') || $actor->can('dashboard.read') || $actor->can('alert.read')))) {
            throw new ItaHttpError(403,'Bạn không có quyền xem phân tích vòng đời tài sản.');
        }
        $this->window=ItaLifecycleRules::window($today ?? (new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d'));
        $keys=array_keys(ItaLifecycleRules::DEFAULTS);
        $rows=$db->fetchAll('SELECT setting_key,setting_value FROM ita_settings WHERE store_id=? AND setting_key IN ('.implode(',',array_fill(0,count($keys),'?')).')','i'.str_repeat('s',count($keys)),[$actor->storeId,...$keys]);
        $values=[];
        foreach ($rows as $row) { $values[$row['setting_key']]=$row['setting_value']; }
        $this->config=ItaLifecycleRules::settings($values);
    }

    public function configuration(): array { return $this->config; }

    private function eligible(array $input): array
    {
        $where="a.store_id=? AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0";
        $params=[$this->actor->storeId];$types='i';
        if (!$this->actor->hasGlobalScope()) {
            $ids=$this->actor->departmentIds;
            $where.=$ids?' AND a.department_id IN ('.implode(',',array_fill(0,count($ids),'?')).')':' AND 1=0';
            $params=[...$params,...$ids];$types.=str_repeat('i',count($ids));
        }
        foreach (['department_id','category_id','id'] as $key) {
            if (($input[$key] ?? '')!=='') {
                $id=ItaInput::positiveInt($input[$key]);
                if ($key==='department_id') { $this->actor->requireDepartment($id); }
                $where.=' AND a.'.$key.'=?';$types.='i';$params[]=$id;
            }
        }
        $q=ItaInput::text($input['q'] ?? '',100);
        if ($q!=='') {
            $pattern='%'.str_replace(['=','%','_'],['==','=%','=_'],$q).'%';
            $where.=" AND (a.asset_code LIKE ? ESCAPE '=' OR a.name LIKE ? ESCAPE '=')";$types.='ss';$params=[...$params,$pattern,$pattern];
        }
        return [$where,$types,$params];
    }

    /** Exact query text and bindings are also exported for user-run EXPLAIN, never executed by exporter. */
    public function queryPlans(array $input,int $page=1): array
    {
        [$where,$types,$params]=$this->eligible($input);$v=$this->config['values'];
        $prefix="WITH cfg AS (SELECT CAST(? AS DATE) as_of,CAST(? AS DECIMAL(12,6)) w_age,CAST(? AS DECIMAL(12,6)) w_repairs,CAST(? AS DECIMAL(12,6)) w_cost,CAST(? AS DECIMAL(12,6)) replace_threshold,CAST(? AS DECIMAL(12,6)) watch_threshold),
        eligible AS (SELECT a.id,a.store_id,a.asset_code,a.name,a.status,a.purchase_date,a.purchase_price,a.category_id,a.department_id,c.name category_name,d.name department_name,c.default_lifespan_years lifespan_years
        FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE $where),
        repair AS (SELECT t.store_id,t.asset_id,COUNT(*) repair_count,SUM(t.cost) repair_cost FROM ita_tickets t JOIN eligible e ON e.store_id=t.store_id AND e.id=t.asset_id
        WHERE t.store_id=? AND t.type='repair' AND t.opened_at>=? AND t.opened_at<? GROUP BY t.store_id,t.asset_id),
        base AS (SELECT e.*,cfg.*,DATEDIFF(cfg.as_of,e.purchase_date) age_days,COALESCE(r.repair_count,0) repair_count,COALESCE(r.repair_cost,0) repair_cost
        FROM eligible e CROSS JOIN cfg LEFT JOIN repair r ON r.store_id=e.store_id AND r.asset_id=e.id),
        ratios AS (SELECT base.*,CASE WHEN age_days>=0 THEN CAST(age_days AS DECIMAL(40,24))/365.25 ELSE NULL END age_years,
        CASE WHEN age_days>=0 AND lifespan_years>0 THEN CAST(age_days AS DECIMAL(40,24))/365.25/lifespan_years ELSE NULL END age_ratio,
        LEAST(CAST(repair_count AS DECIMAL(40,24))/3,1) repairs_ratio,
        CASE WHEN status='broken' THEN 1 WHEN purchase_price>0 THEN LEAST(CAST(repair_cost AS DECIMAL(60,24))/purchase_price,1) ELSE NULL END cost_ratio FROM base),
        scored AS (SELECT ratios.*,w_age*age_ratio age_part,w_repairs*repairs_ratio repairs_part,w_cost*cost_ratio cost_part,
        (w_age*age_ratio+w_repairs*repairs_ratio+w_cost*cost_ratio) score_raw FROM ratios),
        graded AS (SELECT scored.*,CASE WHEN score_raw IS NULL THEN 'missing' WHEN score_raw>=replace_threshold THEN 'replace' WHEN score_raw>=watch_threshold THEN 'watch' ELSE 'normal' END tier FROM scored) ";
        $bind=[$this->window['as_of'],...array_values($v),...$params,$this->actor->storeId,$this->window['start_utc'],$this->window['end_utc']];
        $bindTypes=str_repeat('s',6).$types.'iss';
        $tier=ItaInput::text($input['tier'] ?? '',10);$tail='';
        if ($tier!=='') {
            if (!in_array($tier,['replace','watch','normal','missing'],true)) { throw new ItaHttpError(422,'Nhóm vòng đời không hợp lệ.'); }
            $tail=' WHERE tier=?';$bind[]=$tier;$bindTypes.='s';
        }
        $plan=fn(string $sql,string $extraTypes='',array $extra=[])=>['sql'=>$prefix.$sql,'types'=>$bindTypes.$extraTypes,'params'=>[...$bind,...$extra]];
        $aggregate="COUNT(*) total,SUM(CASE WHEN repair_count>0 THEN 1 ELSE 0 END) failed_assets,COUNT(age_years) age_known,AVG(age_years) average_age_years,SUM(CASE WHEN tier='missing' THEN 1 ELSE 0 END) missing_scores";
        return ['count'=>$plan('SELECT COUNT(*) n FROM graded'.$tail),
            'report'=>$plan('SELECT * FROM graded'.$tail.' ORDER BY score_raw IS NULL,score_raw DESC,id ASC LIMIT 5001'),
            'list'=>$plan('SELECT * FROM graded'.$tail.' ORDER BY score_raw IS NULL,score_raw DESC,id ASC LIMIT 20 OFFSET ?','i',[(max(1,$page)-1)*20]),
            'summary'=>$plan('SELECT '.$aggregate.' FROM graded'.$tail),
            'category'=>$plan('SELECT category_id group_id,category_name group_name,'.$aggregate.' FROM graded'.$tail.' GROUP BY category_id,category_name ORDER BY category_name,category_id'),
            'department'=>$plan('SELECT department_id group_id,department_name group_name,'.$aggregate.' FROM graded'.$tail.' GROUP BY department_id,department_name ORDER BY department_name,department_id')];
    }

    private function all(array $plan): array { return $this->db->fetchAll($plan['sql'],$plan['types'],$plan['params']); }
    private function one(array $plan): ?array { return $this->db->fetchOne($plan['sql'],$plan['types'],$plan['params']); }

    public function listing(array $input,int $page): array
    {
        if ($this->actor->capabilitiesEnabled) { $this->actor->requireCapability('lifecycle.read'); }
        $plans=$this->queryPlans($input,$page);$total=(int)$this->one($plans['count'])['n'];
        $page=max(1,min($page,max(1,(int)ceil($total/20))));$plans=$this->queryPlans($input,$page);
        $rows=array_map(fn($r)=>ItaLifecycleObjectInfo::present($r,$this->config['values']),$this->all($plans['list']));
        $category=$this->all($plans['category']);$department=$this->all($plans['department']);
        $categories=$this->db->fetchAll('SELECT id,name FROM ita_asset_categories WHERE store_id=? AND is_software=0 ORDER BY name,id LIMIT 501','i',[$this->actor->storeId]);
        if (count($categories)>500) { throw new ItaHttpError(422,'Danh sách loại vượt 500; cần thu hẹp trước khi phân tích vòng đời.'); }
        return ['rows'=>$rows,'page'=>$page,'per_page'=>20,'total'=>$total,'window'=>$this->window,'config'=>$this->config,
            'summary'=>$this->one($plans['summary']),'by_category'=>$category,'by_department'=>$department,
            'departments'=>(new ItaAssetObjects($this->db,$this->actor))->departments(),
            'categories'=>$categories];
    }

    public function detail(int $id): array
    {
        if ($this->actor->capabilitiesEnabled) { $this->actor->requireCapability('lifecycle.read'); }
        $asset=$this->db->fetchOne('SELECT id,department_id FROM ita_assets WHERE store_id=? AND id=? AND is_deleted=0','ii',[$this->actor->storeId,$id]);
        if (!$asset) { throw new ItaHttpError(404,'Không tìm thấy thiết bị.'); }
        $this->actor->requireDepartment($asset['department_id']===null?null:(int)$asset['department_id']);
        $row=$this->all($this->queryPlans(['id'=>$id])['list'])[0] ?? null;
        if (!$row) { throw new ItaHttpError(404,'Thiết bị không thuộc phạm vi phân tích vòng đời.'); }
        return ['asset'=>ItaLifecycleObjectInfo::present($row,$this->config['values']),'window'=>$this->window,'config'=>$this->config];
    }
}
