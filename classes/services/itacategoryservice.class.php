<?php
class ItaCategoryService
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    public function listing(): array
    {
        $rows = $this->db->fetchAll('SELECT id,code,name,status FROM ita_asset_categories WHERE store_id=? ORDER BY name LIMIT 501', 'i', [$this->actor->storeId]);
        if (count($rows)>500) {
            throw new ItaHttpError(422,'Danh mục vượt giới hạn 500 loại. Cần bổ sung phân trang trước khi tiếp tục.');
        }
        return $rows;
    }

    /** Admin catalog browser; the bounded dropdown contract remains separate. */
    public function paginated(array $input, int $page): array
    {
        $this->actor->requireAdmin();
        if (($input['paged'] ?? null) !== '1') {
            throw new ItaHttpError(422,'Chế độ danh mục không hợp lệ.');
        }
        $query = ItaInput::text($input['q'] ?? '',100);
        $where = 'store_id=?';
        $types = 'i';
        $params = [$this->actor->storeId];
        if ($query !== '') {
            $like = '%'.str_replace(['!','%','_'],['!!','!%','!_'],$query).'%';
            $where .= " AND (code LIKE ? ESCAPE '!' OR name LIKE ? ESCAPE '!')";
            $types .= 'ss';
            $params = [...$params,$like,$like];
        }
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) n FROM ita_asset_categories WHERE '.$where,$types,$params)['n'];
        $page = max(1,min($page,max(1,(int) ceil($total/20))));
        $rows = $this->db->fetchAll('SELECT id,code,name,status FROM ita_asset_categories WHERE '.$where.' ORDER BY name,id LIMIT 20 OFFSET ?',$types.'i',[...$params,($page-1)*20]);
        return ['rows'=>array_map(fn($r)=>$r+['version'=>ItaAssetService::version($r)],$rows),'page'=>$page,'per_page'=>20,'total'=>$total];
    }

    /** Preserve used categories; deactivation hides them from new asset choices. */
    public function save(array $input): int
    {
        $this->actor->requireAdmin();
        $id = ($input['id'] ?? '') === '' ? null : ItaInput::positiveInt($input['id']);
        $code = ItaInput::text($input['code'] ?? '',50);
        $name = ItaInput::text($input['name'] ?? '',150);
        $status = ItaInput::text($input['status'] ?? '',10);
        if ($code === '' || $name === '' || !in_array($status,['active','inactive'],true)) {
            throw new ItaHttpError(422,'Vui lòng nhập mã, tên và trạng thái danh mục hợp lệ.');
        }
        $this->db->beginTransaction();
        try {
            $before = $id ? $this->db->fetchOne('SELECT id,code,name,status FROM ita_asset_categories WHERE store_id=? AND id=? FOR UPDATE','ii',[$this->actor->storeId,$id]) : null;
            if ($id && !$before) {
                throw new ItaHttpError(404,'Không tìm thấy danh mục.');
            }
            if ($before && !hash_equals(ItaAssetService::version($before),ItaInput::text($input['version'] ?? '',64))) {
                throw new ItaHttpError(409,'Danh mục đã thay đổi. Hãy tải lại trước khi lưu.');
            }
            if ($id) {
                $this->db->execute('UPDATE ita_asset_categories SET code=?,name=?,status=? WHERE store_id=? AND id=?','sssii',[$code,$name,$status,$this->actor->storeId,$id]);
            } else {
                $this->db->execute('INSERT INTO ita_asset_categories (store_id,code,name,status) VALUES (?,?,?,?)','isss',[$this->actor->storeId,$code,$name,$status]);
                $id = (int) $this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
            }
            (new ItaAuditService($this->db,$this->actor))->record('category.update','category',$id,['before'=>$before,'after'=>['code'=>$code,'name'=>$name,'status'=>$status]]);
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof mysqli_sql_exception && (int) $error->getCode() === 1062) {
                throw new ItaHttpError(409,'Mã danh mục đã tồn tại.');
            }
            throw $error;
        }
    }
}
