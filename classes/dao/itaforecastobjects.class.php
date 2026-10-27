<?php
class ItaForecastObjects
{
    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor)
    {
        ItaForecastRules::access($actor);
    }

    public function configuration(): array
    {
        $rows=$this->db->fetchAll('SELECT id,name,default_replacement_cost FROM ita_asset_categories WHERE store_id=? AND is_software=0 ORDER BY id LIMIT 501','i',[$this->actor->storeId]);
        if (count($rows)>500) { throw new ItaHttpError(422,'Vượt 500 loại thiết bị.'); }
        $setting=$this->db->fetchOne("SELECT setting_value FROM ita_settings WHERE store_id=? AND setting_key='forecast_category_parameters'",'i',[$this->actor->storeId]);
        try { $values=$setting ? json_decode($setting['setting_value'],true,32,JSON_THROW_ON_ERROR) : []; }
        catch (Throwable $e) { throw new ItaHttpError(422,'Cấu hình ngân sách không hợp lệ.'); }
        if (!is_array($values)) { throw new ItaHttpError(422,'Cấu hình ngân sách không hợp lệ.'); }
        $categories=[];
        foreach ($rows as $r) {
            $p=$values[(string)$r['id']] ?? [];
            if (!is_array($p)) { throw new ItaHttpError(422,'Cấu hình loại thiết bị không hợp lệ.'); }
            $categories[]=['id'=>(int)$r['id'],'name'=>$r['name'],'price'=>$r['default_replacement_cost'],
                'units_per_person'=>ItaForecastRules::parameter($p['units_per_person'] ?? null,1000),
                'maintenance_percent'=>ItaForecastRules::parameter($p['maintenance_percent'] ?? null,100)];
        }
        return ['categories'=>$categories,'version'=>ItaForecastObjectInfo::version(['categories'=>$categories,'setting'=>$setting]),
            'contingency_percent'=>'10','departments'=>$this->departments(),'can_configure'=>$this->actor->can('forecast.configure')];
    }

    private function departments(): array
    {
        $where='store_id=?';$params=[$this->actor->storeId];
        if (!$this->actor->hasGlobalScope()) {
            $ids=$this->actor->departmentIds;
            $where.=$ids?' AND id IN ('.implode(',',array_fill(0,count($ids),'?')).')':' AND 1=0';
            $params=[...$params,...$ids];
        }
        $rows=$this->db->fetchAll('SELECT id,name FROM ita_departments WHERE '.$where.' ORDER BY id LIMIT 501',str_repeat('i',count($params)),$params);
        if (count($rows)>500) { throw new ItaHttpError(422,'Vượt 500 bộ phận; cần thu hẹp phạm vi.'); }
        return $rows;
    }

    public function plans(array $input): array
    {
        $year=ItaForecastRules::year($input['year'] ?? ItaForecastRules::currentYear());
        $where='store_id=? AND year=?';$types='ii';$params=[$this->actor->storeId,$year];
        if (!$this->actor->hasGlobalScope()) {
            $ids=$this->actor->departmentIds;
            if (count($ids)>500) { throw new ItaHttpError(422,'Phạm vi bộ phận vượt giới hạn.'); }
            $where.=$ids?' AND department_id IN ('.implode(',',array_fill(0,count($ids),'?')).')':' AND 1=0';
            $types.=str_repeat('i',count($ids));$params=[...$params,...$ids];
        }
        if (!empty($input['department_id'])) {
            $id=ItaInput::positiveInt($input['department_id']);$this->actor->requireDepartment($id);
            $where.=' AND department_id=?';$types.='i';$params[]=$id;
        }
        $rows=$this->db->fetchAll('SELECT * FROM ita_budget_plans WHERE '.$where.' ORDER BY department_id LIMIT 501',$types,$params);
        if (count($rows)>500) { throw new ItaHttpError(422,'Vượt giới hạn 500 kế hoạch.'); }
        return array_map(function($r) {
            $hasSnapshot=!empty($r['calculation_snapshot_json']);
            return ['id'=>(int)$r['id'],'department_id'=>(int)$r['department_id'],'year'=>(int)$r['year'],
                'planned_headcount_increase'=>(int)$r['planned_headcount_increase'],'note'=>$r['note'],'status'=>$r['status'],
                'version'=>ItaForecastObjectInfo::version($r),'has_snapshot'=>$hasSnapshot];
        },$rows);
    }

    public function saved(int $id,int $categoryId=0): array
    {
        $row=$this->db->fetchOne('SELECT * FROM ita_budget_plans WHERE store_id=? AND id=?','ii',[$this->actor->storeId,$id]);
        if (!$row) { throw new ItaHttpError(404,'Không tìm thấy kế hoạch.'); }
        $this->actor->requireDepartment((int)$row['department_id']);
        $snapshot=ItaForecastObjectInfo::snapshot($row);
        if ($categoryId) {
            $snapshot['rows']=array_values(array_filter($snapshot['rows'],fn($r)=>(int)$r['category_id']===$categoryId));
            if (!$snapshot['rows']) { throw new ItaHttpError(404,'Loại thiết bị không có trong snapshot này.'); }
            $snapshot['summary']=ItaForecastRules::summary($snapshot['rows']);$snapshot['category_id']=$categoryId;
        }
        return $snapshot;
    }

    /** Exact bounded source and preaggregate queries, exported unchanged for EXPLAIN. */
    public function queryPlans(array $input): array
    {
        $lifecycle=(new ItaLifecycleObjects($this->db,$this->actor))->queryPlans(array_intersect_key($input,array_flip(['department_id','category_id'])));
        $assets=$lifecycle['list'];$at=strrpos($assets['sql'],' ORDER BY score_raw');
        $assets['sql']=substr($assets['sql'],0,$at).' ORDER BY id LIMIT '.(ItaForecastRules::MAX_ASSETS+1);
        array_pop($assets['params']);$assets['types']=substr($assets['types'],0,-1);
        $now=new DateTimeImmutable('first day of January this year 00:00:00',new DateTimeZone('Asia/Ho_Chi_Minh'));
        $where="a.store_id=? AND a.is_deleted=0 AND a.status<>'retired' AND c.is_software=0";$types='i';$params=[$this->actor->storeId];
        if (!$this->actor->hasGlobalScope()) {
            $ids=$this->actor->departmentIds;
            $where.=$ids?' AND a.department_id IN ('.implode(',',array_fill(0,count($ids),'?')).')':' AND 1=0';
            $types.=str_repeat('i',count($ids));$params=[...$params,...$ids];
        }
        foreach (['department_id','category_id'] as $key) {
            if (!empty($input[$key])) {
                $id=ItaInput::positiveInt($input[$key]);
                if ($key==='department_id') { $this->actor->requireDepartment($id); }
                $where.=' AND a.'.$key.'=?';$types.='i';$params[]=$id;
            }
        }
        $start=$now->modify('-3 years')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $end=$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        return ['assets'=>$assets,'history'=>['sql'=>"SELECT t.asset_id,COUNT(*) n,COUNT(t.cost) known,SUM(t.cost) cost FROM ita_tickets t JOIN ita_assets a ON a.store_id=t.store_id AND a.id=t.asset_id JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id WHERE $where AND t.type IN ('repair','periodic') AND t.status IN ('resolved','failed') AND t.opened_at>=? AND t.opened_at<? GROUP BY t.asset_id ORDER BY t.asset_id LIMIT ".(ItaForecastRules::MAX_ASSETS+1),
            'types'=>$types.'ss','params'=>[...$params,$start,$end]],'window'=>['start_utc'=>$start,'end_utc'=>$end]];
    }

    public function calculate(array $input,?int $overrideHeadcount=null): array
    {
        $year=ItaForecastRules::year($input['year'] ?? ItaForecastRules::currentYear());$config=$this->configuration();
        $departments=$config['departments'];
        if (!empty($input['department_id'])) {
            $id=ItaInput::positiveInt($input['department_id']);$this->actor->requireDepartment($id);
            $departments=array_values(array_filter($departments,fn($d)=>(int)$d['id']===$id));
            if (!$departments) { throw new ItaHttpError(404,'Không tìm thấy bộ phận.'); }
        }
        $categories=$config['categories'];
        if (!empty($input['category_id'])) {
            $id=ItaInput::positiveInt($input['category_id']);$categories=array_values(array_filter($categories,fn($c)=>$c['id']===$id));
            if (!$categories) { throw new ItaHttpError(404,'Không tìm thấy loại thiết bị.'); }
        }
        if (count($departments)*count($categories)>ItaForecastRules::MAX_ROWS) { throw new ItaHttpError(422,'Vượt 1.000 dòng; hãy lọc bộ phận hoặc loại.'); }
        $queries=$this->queryPlans($input);$source=[];
        foreach (['assets','history'] as $key) {
            $p=$queries[$key];$source[$key]=$this->db->fetchAll($p['sql'],$p['types'],$p['params']);
            if (count($source[$key])>ItaForecastRules::MAX_ASSETS) { throw new ItaHttpError(422,'Vượt 2.000 thiết bị; hãy thu hẹp phạm vi.'); }
        }
        $history=array_column($source['history'],null,'asset_id');$groups=[];$params=[];
        foreach ($departments as $d) {
            foreach ($categories as $c) {
                $key=$d['id'].':'.$c['id'];$params[$key]=$c;
                $groups[$key]=['department_id'=>(int)$d['id'],'department_name'=>$d['name'],'category_id'=>$c['id'],'category_name'=>$c['name'],
                    'asset_count'=>0,'replacement_count'=>0,'unknown_replacement'=>0,'history_count'=>0,'history_missing'=>0,'history_cents'=>'0','missing'=>[],'asset_inputs'=>[]];
            }
        }
        $unassigned=0;
        foreach ($source['assets'] as $a) {
            $key=$a['department_id'].':'.$a['category_id'];
            if (!isset($groups[$key])) { $unassigned++;continue; }
            $g=&$groups[$key];$g['asset_count']++;
            $due=ItaForecastRules::replacement($a,$year);
            if ($due===true) { $g['replacement_count']++; } elseif ($due===null) { $g['unknown_replacement']++; }
            $h=$history[$a['id']] ?? ['n'=>0,'known'=>0,'cost'=>'0'];
            $g['asset_inputs'][]=array_intersect_key($a,array_flip(['id','asset_code','status','purchase_date','purchase_price','lifespan_years','score_raw','tier','repair_count','repair_cost']))+['history'=>$h,'replacement_eligible'=>$due];
            $g['history_count']+=(int)$h['n'];$g['history_missing']+=(int)$h['n']-(int)$h['known'];
            $g['history_cents']=ItaForecastMoney::add($g['history_cents'],ItaForecastMoney::units($h['cost'] ?? '0'));
            unset($g);
        }
        $plans=array_column($this->plans(['year'=>$year]),null,'department_id');$rows=[];
        foreach ($groups as $key=>$group) {
            $headcount=$overrideHeadcount ?? (isset($plans[$group['department_id']])?$plans[$group['department_id']]['planned_headcount_increase']:-1);
            $rows[]=ItaForecastRules::row($group,$params[$key],$headcount);
        }
        $summary=ItaForecastRules::summary($rows);
        if ($unassigned) { $summary['complete']=false; }
        return ['schema_version'=>1,'formula_version'=>'forecast-1','year'=>$year,'as_of'=>(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format(DATE_ATOM),
            'history_window'=>$queries['window'],'rows'=>$rows,'summary'=>$summary,'unassigned_assets'=>$unassigned,
            'saved'=>false,'config'=>$config,'plans'=>array_values($plans),'lifecycle_config'=>(new ItaLifecycleObjects($this->db,$this->actor))->configuration()];
    }
}
