<?php
class ItaNetworkObjects
{
    private const JOINS=' FROM ita_network_links l JOIN ita_assets a ON a.store_id=l.store_id AND a.id=l.source_asset_id
        JOIN ita_assets b ON b.store_id=l.store_id AND b.id=l.target_asset_id
        JOIN ita_asset_categories ca ON ca.store_id=a.store_id AND ca.id=a.category_id
        JOIN ita_asset_categories cb ON cb.store_id=b.store_id AND cb.id=b.category_id';

    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor) { ItaNetworkRules::access($actor); }

    private function scope(string $alias): array
    {
        $where=$alias.'.store_id=? AND '.$alias.'.is_deleted=0';$params=[$this->actor->storeId];
        if (!$this->actor->isAdmin()) {
            $ids=$this->actor->departmentIds;
            $where.=$ids?' AND '.$alias.'.department_id IN ('.implode(',',array_fill(0,count($ids),'?')).')':' AND 1=0';
            $params=[...$params,...$ids];
        }
        return [$where,str_repeat('i',count($params)),$params];
    }

    public function detail(int $id): array
    {
        $row=$this->db->fetchOne('SELECT l.*,a.name source_name,b.name target_name,a.department_id source_department,b.department_id target_department,a.is_deleted source_deleted,b.is_deleted target_deleted,ca.is_software source_software,cb.is_software target_software'.self::JOINS.' WHERE l.store_id=? AND l.id=? AND l.is_deleted=0','ii',[$this->actor->storeId,$id]);
        if (!$row) { throw new ItaHttpError(404,'Không tìm thấy liên kết.'); }
        foreach (['source','target'] as $end) {
            $this->actor->requireDepartment($row[$end.'_department']===null?null:(int)$row[$end.'_department']);
            if ((int)$row[$end.'_deleted'] || (int)$row[$end.'_software']) { throw new ItaHttpError(404,'Liên kết không còn đủ điều kiện.'); }
        }
        return ItaNetworkObjectInfo::row($row);
    }

    /** Nodes, graph and paged table share one bounded, scope-filtered asset set. */
    public function listing(array $input,int $page): array
    {
        [$where,$types,$params]=$this->scope('a');$where.=' AND c.is_software=0';
        $q=ItaInput::text($input['q'] ?? '',100);
        if ($q!=='') {
            $pattern='%'.str_replace(['!','%','_'],['!!','!%','!_'],$q).'%';
            $where.=" AND (a.name LIKE ? ESCAPE '!' OR a.asset_code LIKE ? ESCAPE '!')";$types.='ss';$params=[...$params,$pattern,$pattern];
        }
        if (($input['department_id'] ?? '')!=='') {
            $department=ItaInput::positiveInt($input['department_id']);$this->actor->requireDepartment($department);
            $where.=' AND a.department_id=?';$types.='i';$params[]=$department;
        }
        $nodes=$this->db->fetchAll('SELECT a.id,a.asset_code,a.name,a.status,a.department_id,c.name kind,d.name department FROM ita_assets a JOIN ita_asset_categories c ON c.store_id=a.store_id AND c.id=a.category_id LEFT JOIN ita_departments d ON d.store_id=a.store_id AND d.id=a.department_id WHERE '.$where.' ORDER BY a.id LIMIT 201',$types,$params);
        if (count($nodes)>200) { throw new ItaHttpError(422,'Sơ đồ vượt 200 thiết bị. Hãy thu hẹp bộ phận hoặc tìm kiếm.'); }
        $ids=array_map('intval',array_column($nodes,'id'));
        [$a,$at,$ap]=$this->scope('a');[$b,$bt,$bp]=$this->scope('b');
        $where='l.store_id=? AND l.is_deleted=0 AND '.$a.' AND '.$b.' AND ca.is_software=0 AND cb.is_software=0';
        $types='i'.$at.$bt;$params=[$this->actor->storeId,...$ap,...$bp];
        if (!$ids) { $where.=' AND 1=0'; }
        else {
            $slots=implode(',',array_fill(0,count($ids),'?'));$where.=' AND a.id IN ('.$slots.') AND b.id IN ('.$slots.')';
            $types.=str_repeat('i',2*count($ids));$params=[...$params,...$ids,...$ids];
        }
        $from=self::JOINS.' WHERE '.$where;
        $total=(int)$this->db->fetchOne('SELECT COUNT(*) n'.$from,$types,$params)['n'];
        if ($total>400) { throw new ItaHttpError(422,'Sơ đồ vượt 400 liên kết. Hãy thu hẹp phạm vi.'); }
        $links=$this->db->fetchAll('SELECT l.id,l.source_asset_id,l.target_asset_id,l.link_type,l.capacity_label'.$from.' ORDER BY l.id LIMIT 401',$types,$params);
        if (count($links)>400) { throw new ItaHttpError(422,'Sơ đồ vượt giới hạn liên kết.'); }
        $page=max(1,min($page,max(1,(int)ceil($total/20))));
        $rows=$this->db->fetchAll('SELECT l.*,a.name source_name,b.name target_name'.$from.' ORDER BY l.id DESC LIMIT 20 OFFSET ?',$types.'i',[...$params,($page-1)*20]);
        return ['mock'=>false,'can_manage'=>$this->actor->isAdmin(),'nodes'=>$nodes,'links'=>$links,'rows'=>array_map([ItaNetworkObjectInfo::class,'row'],$rows),'page'=>$page,'per_page'=>20,'total'=>$total];
    }
}
