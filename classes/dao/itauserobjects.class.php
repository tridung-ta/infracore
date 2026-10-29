<?php
class ItaUserObjects
{
    public function __construct(private ItaDb $db)
    {
    }

    public function identity(int $storeId, string $identity): ?array
    {
        $rows = $this->db->fetchAll('SELECT id,username,status FROM dc_users WHERE store_id=? AND (username=? OR email=?) LIMIT 2', 'iss', [$storeId,$identity,$identity]);
        return count($rows) === 1 ? $rows[0] : null;
    }

    public function current(int $storeId, int $userId): ?ItaUserObjectInfo
    {
        $row = $this->db->fetchOne('SELECT u.id,u.username,u.fullname,r.role,r.auth_revision FROM dc_users u
            JOIN ita_user_roles r ON r.store_id=u.store_id AND r.user_id=u.id
            WHERE u.store_id=? AND u.id=? AND u.status=1 AND r.enabled=1', 'ii', [$storeId,$userId]);
        if (!$row) {
            return null;
        }
        $scopes = $this->db->fetchAll('SELECT s.department_id FROM ita_user_scopes s JOIN ita_departments d
            ON d.store_id=s.store_id AND d.id=s.department_id WHERE s.store_id=? AND s.user_id=? AND s.active=1 AND d.status=\'active\'', 'ii', [$storeId,$userId]);
        $row['store_id'] = $storeId;
        if (ItaPermissions::enabled()) {
            $permissions=$this->db->fetchAll('SELECT permission_key FROM ita_user_permissions WHERE store_id=? AND user_id=? AND active=1 ORDER BY permission_key','ii',[$storeId,$userId]);
            $row['permissions']=array_column($permissions,'permission_key');
            try { ItaPermissions::validate($row['permissions']);ItaPermissions::dependencies($row['permissions']); }
            catch (ItaHttpError $error) { throw new ItaHttpError(503,'Cấu hình quyền chức năng chưa sẵn sàng.'); }
        }
        return new ItaUserObjectInfo($row, array_map(fn($r)=>(int) $r['department_id'], $scopes));
    }

    public function listing(ItaUserObjectInfo $actor, int $page, array $filters=[]): array
    {
        $actor->requireCapability('access.read');
        [$where,$types,$params]=$this->listingFilters($actor,$filters);
        $from=' FROM dc_users u LEFT JOIN ita_user_roles r ON r.store_id=u.store_id AND r.user_id=u.id WHERE '.$where;
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n'.$from,$types,$params)['n'];
        $page = max(1, min($page, max(1, (int) ceil($total/20))));
        $rows = $this->db->fetchAll('SELECT u.id,u.username,u.fullname,u.status,COALESCE(r.role,\'\') role,COALESCE(r.enabled,0) enabled
            '.$from.' ORDER BY u.id LIMIT 20 OFFSET ?',$types.'i',[...$params,($page-1)*20]);
        return ['rows'=>$rows,'page'=>$page,'per_page'=>20,'total'=>$total];
    }

    private function listingFilters(ItaUserObjectInfo $actor,array $filters): array
    {
        $where=['u.store_id=?'];$types='i';$params=[$actor->storeId];
        $q=ItaInput::text($filters['q'] ?? '',100);
        if ($q!=='') {
            $where[]="(u.username LIKE ? ESCAPE '!' OR u.fullname LIKE ? ESCAPE '!')";
            $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],$q).'%';
            $types.='ss';array_push($params,$term,$term);
        }
        $status=ItaInput::text($filters['access_status'] ?? '',20);
        $conditions=['allowed'=>'u.status=1 AND r.enabled=1','not_allowed'=>'u.status=1 AND COALESCE(r.enabled,0)<>1','locked'=>'u.status<>1'];
        if ($status!=='') {
            if (!isset($conditions[$status])) { throw new ItaHttpError(422,'Trạng thái tài khoản không hợp lệ. Hãy chọn lại trong bộ lọc.'); }
            $where[]='('.$conditions[$status].')';
        }
        if (($filters['department_id'] ?? '')!=='') {
            $id=ItaInput::positiveInt($filters['department_id']);
            $where[]='EXISTS (SELECT 1 FROM ita_user_scopes s WHERE s.store_id=u.store_id AND s.user_id=u.id AND s.active=1 AND s.department_id=?)';
            $types.='i';$params[]=$id;
        }
        return [implode(' AND ',$where),$types,$params];
    }

    public function access(ItaUserObjectInfo $actor, int $userId): array
    {
        $actor->requireCapability('access.manage');
        $user = $this->db->fetchOne('SELECT u.id,u.username,u.status,r.role,r.enabled FROM dc_users u
            LEFT JOIN ita_user_roles r ON r.store_id=u.store_id AND r.user_id=u.id WHERE u.store_id=? AND u.id=?', 'ii', [$actor->storeId,$userId]);
        if (!$user) {
            throw new ItaHttpError(404, 'Không tìm thấy tài khoản.');
        }
        $scopes = $this->db->fetchAll('SELECT department_id FROM ita_user_scopes WHERE store_id=? AND user_id=? AND active=1', 'ii', [$actor->storeId,$userId]);
        $user['department_ids'] = array_map(fn($r)=>(int) $r['department_id'], $scopes);
        if (ItaPermissions::enabled()) {
            $user['permissions']=array_column($this->db->fetchAll('SELECT permission_key FROM ita_user_permissions WHERE store_id=? AND user_id=? AND active=1 ORDER BY permission_key','ii',[$actor->storeId,$userId]),'permission_key');
            $revision=$this->db->fetchOne('SELECT auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=?','ii',[$actor->storeId,$userId]);
            $user['capabilities_enabled']=true;
            sort($user['department_ids']);
            $user['version']=hash('sha256',json_encode([$user,$revision],JSON_THROW_ON_ERROR));
        }
        return $user;
    }
}
