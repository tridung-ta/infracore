<?php
/** Validate immutable source rows against current reference data and actor scope. */
class ItaImportRows
{
    private array $references = [];

    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor)
    {
    }

    public function validate(array $rows, bool $locking = false): array
    {
        $this->references = [];
        $codes = array_column(array_column($rows,'values'),'asset_code');
        $keys = $this->weights($codes);
        $existing = $this->existing($codes);
        $counts = array_count_values($keys);
        $result = [];
        foreach ($rows as $index=>$row) {
            try {
                if ($counts[$keys[$index]]>1 || isset($existing[$keys[$index]])) {
                    throw new ItaHttpError(422,'Mã tài sản trùng trong file hoặc đã có trong hệ thống (kể cả thùng rác).');
                }
                $values = $this->fields($row['values'],$locking);
                $result[] = array_replace($row,['fields'=>$values,'error'=>'']);
            } catch (ItaHttpError $error) {
                $result[] = array_replace($row,['error'=>$error->getMessage()]);
            }
        }
        return $result;
    }

    private function fields(array $input, bool $locking): array
    {
        foreach (array_slice(ItaExcel::HEADERS,0,7) as $required) {
            if (($input[$required] ?? '') === '') {
                throw new ItaHttpError(422,'Thiếu cột bắt buộc: '.$required.'.');
            }
        }
        $category = $this->reference('ita_asset_categories',$input['category_code'],$locking);
        $department = $this->reference('ita_departments',$input['department_code'],$locking);
        $this->actor->requireDepartment((int) $department['id']);
        $values = ItaAssetValidation::fields($input+['category_id'=>$category['id'],'department_id'=>$department['id']]);
        $status = ItaInput::text($input['status'],20);
        if (!in_array($status,['in_use','maintenance','broken','retired'],true)) {
            throw new ItaHttpError(422,'Trạng thái không hợp lệ.');
        }
        $reason = ItaInput::text($input['status_reason'] ?? '',10000);
        if ($status==='retired') {
            $this->actor->requireAdmin();
            if ($reason==='') {
                throw new ItaHttpError(422,'Tài sản thanh lý cần lý do.');
            }
        }
        return $values+['status'=>$status,'status_reason'=>$reason];
    }

    private function reference(string $table, string $code, bool $locking): array
    {
        $key = $table.':'.$code;
        if (!isset($this->references[$key])) {
            $row = $this->db->fetchOne("SELECT id FROM $table WHERE store_id=? AND code=? AND status='active'".($locking?' FOR UPDATE':''),'is',[$this->actor->storeId,$code]);
            if (!$row) {
                throw new ItaHttpError(422,'Mã loại hoặc bộ phận không tồn tại/đã ngưng hoạt động.');
            }
            $this->references[$key] = $row;
        }
        return $this->references[$key];
    }

    /** Use the actual database collation, including accents/case/trailing-space equivalence. */
    private function weights(array $codes): array
    {
        $keys = [];
        foreach (array_chunk($codes,100) as $chunk) {
            $selects = [];
            foreach ($chunk as $index=>$code) {
                $selects[] = 'SELECT '.$index." ordinal,HEX(WEIGHT_STRING(RTRIM(CONVERT(? USING utf8mb4)) COLLATE utf8mb4_unicode_ci)) weight_key";
            }
            $rows = $this->db->fetchAll(implode(' UNION ALL ',$selects).' ORDER BY ordinal',str_repeat('s',count($chunk)),$chunk);
            foreach ($rows as $row) {
                $keys[] = $row['weight_key'];
            }
        }
        if (count($keys)!==count($codes)) {
            throw new RuntimeException('Collation validation unavailable');
        }
        return $keys;
    }

    private function existing(array $codes): array
    {
        $existing = [];
        foreach (array_chunk($codes,100) as $chunk) {
            $marks = implode(',',array_fill(0,count($chunk),'?'));
            $rows = $this->db->fetchAll("SELECT HEX(WEIGHT_STRING(RTRIM(asset_code) COLLATE utf8mb4_unicode_ci)) weight_key
                FROM ita_assets WHERE store_id=? AND asset_code IN ($marks)",'i'.str_repeat('s',count($chunk)),[$this->actor->storeId,...$chunk]);
            foreach ($rows as $row) {
                $existing[$row['weight_key']] = true;
            }
        }
        return $existing;
    }
}
