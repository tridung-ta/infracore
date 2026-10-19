<?php
class ItaImportService
{
    public function __construct(private ItaDb $db, private ItaUserObjectInfo $actor, private string $sessionKey)
    {
        if (!in_array($actor->role,['admin_it','system_manager'],true)) {
            throw new ItaHttpError(403,'Bạn không có quyền import tài sản.');
        }
    }

    public function upload(string $path, string $filename): array
    {
        $name = ItaInput::text(basename(str_replace('\\','/',$filename)),255);
        $rows = ItaExcel::read($path,$name);
        $rows = (new ItaImportRows($this->db,$this->actor))->validate($rows);
        $rows = array_map(static function (array $row): array {
            unset($row['fields']);
            return $row;
        },$rows);
        $rows[0]['revision'] = $this->actor->revision;
        $file = (new ItaImportStorage())->put($rows);
        $errors = count(array_filter($rows,fn($row)=>$row['error']!==''));
        $this->db->execute("INSERT INTO ita_import_batches (store_id,user_id,filename,staging_file,total_rows,ok_rows,error_rows,status,expires_at)
            VALUES (?,?,?,?,?,?,?,'validated',DATE_ADD(UTC_TIMESTAMP(),INTERVAL 2 HOUR))",'iissiii',
            [$this->actor->storeId,$this->actor->id,$name,$file,count($rows),count($rows)-$errors,$errors]);
        $id = (int) $this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
        return $this->preview($id,1);
    }

    public function preview(int $id, int $page): array
    {
        $batch = $this->batch($id);
        $rows = $batch['status']==='completed' ? [] : $this->source($batch);
        $page = max(1,min($page,(int) ceil(max(1,count($rows))/20)));
        return ['batch_id'=>$id,'filename'=>$batch['filename'],'status'=>$batch['status'],'total_rows'=>(int) $batch['total_rows'],
            'ok_rows'=>(int) $batch['ok_rows'],'error_rows'=>(int) $batch['error_rows'],'expires_at'=>$batch['expires_at'],
            'confirm_token'=>$this->token($batch),'page'=>$page,'per_page'=>20,
            'rows'=>array_map(fn($row)=>['line'=>$row['line'],'asset_code'=>$row['values']['asset_code'],
                'name'=>$row['values']['name'],'error'=>$row['error']],array_slice($rows,($page-1)*20,20))];
    }

    public function errors(int $id): string
    {
        $batch = $this->batch($id);
        $rows = $this->source($batch);
        $errors = array_filter($rows,fn($row)=>$row['error']!=='');
        return ItaExcel::write(['line',...ItaExcel::HEADERS,'error'],array_map(fn($row)=>[$row['line'],...array_values($row['values']),$row['error']],array_values($errors)));
    }

    /** Batch lock makes retries idempotent; all asset rows, audits and completion share one transaction. */
    public function confirm(array $input): array
    {
        $id = ItaInput::positiveInt($input['batch_id'] ?? null);
        $token = ItaInput::text($input['confirm_token'] ?? '',64);
        $this->db->beginTransaction();
        try {
            $this->lockActor();
            $batch = $this->batch($id,true);
            if (!hash_equals($this->token($batch),$token)) {
                throw new ItaHttpError(409,'Xác nhận không hợp lệ. Hãy xem trước lại file.');
            }
            if ($batch['status']==='completed') {
                $this->db->commit();
                return ['batch_id'=>$id,'status'=>'completed','imported_rows'=>(int) $batch['ok_rows'],'already_completed'=>true];
            }
            if ($batch['status']!=='validated' || (int) $batch['error_rows']!==0) {
                throw new ItaHttpError(409,'Chỉ xác nhận lô đã kiểm tra và không có lỗi.');
            }
            $rows = (new ItaImportRows($this->db,$this->actor))->validate($this->source($batch),true);
            if (array_filter($rows,fn($row)=>$row['error']!=='')) {
                throw new ItaHttpError(409,'Dữ liệu hoặc quyền đã thay đổi. Chưa ghi tài sản nào; hãy tải lại file để kiểm tra.');
            }
            $this->db->execute("UPDATE ita_import_batches SET status='importing' WHERE store_id=? AND id=?",'ii',[$this->actor->storeId,$id]);
            foreach ($rows as $row) {
                $this->insert($row['fields']);
            }
            $this->db->execute("UPDATE ita_import_batches SET status='completed' WHERE store_id=? AND id=?",'ii',[$this->actor->storeId,$id]);
            (new ItaAuditService($this->db,$this->actor))->record('import.complete','import_batch',$id,['after'=>['total_rows'=>count($rows)]]);
            $this->db->commit();
            return ['batch_id'=>$id,'status'=>'completed','imported_rows'=>count($rows),'already_completed'=>false];
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof mysqli_sql_exception && (int) $error->getCode()===1062) {
                throw new ItaHttpError(409,'Có mã tài sản vừa được thêm đồng thời. Toàn bộ lô chưa được ghi; hãy tải lại file.');
            }
            throw $error;
        }
    }

    private function batch(int $id, bool $locking = false): array
    {
        $batch = $this->db->fetchOne('SELECT *,expires_at>UTC_TIMESTAMP() available FROM ita_import_batches WHERE store_id=? AND user_id=? AND id=?'.($locking?' FOR UPDATE':''),
            'iii',[$this->actor->storeId,$this->actor->id,$id]);
        if (!$batch) {
            throw new ItaHttpError(404,'Không tìm thấy lô import của bạn.');
        }
        if (!(int) $batch['available'] && $batch['status']!=='completed') {
            throw new ItaHttpError(410,'Bản xem trước đã hết hạn. Hãy tải lại file.');
        }
        return $batch;
    }

    private function source(array $batch): array
    {
        $rows = (new ItaImportStorage())->get($batch['staging_file'] ?? '');
        if (($rows[0]['revision'] ?? null)!==$this->actor->revision) {
            throw new ItaHttpError(409,'Quyền đã thay đổi từ lúc tải file. Hãy tải lại file.');
        }
        return $rows;
    }

    private function token(array $batch): string
    {
        return hash_hmac('sha256',implode(':',[$batch['id'],$batch['store_id'],$batch['user_id'],$batch['staging_file']]),$this->sessionKey);
    }

    private function lockActor(): void
    {
        $row = $this->db->fetchOne('SELECT role,enabled,auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=? FOR UPDATE','ii',[$this->actor->storeId,$this->actor->id]);
        if (!$row || !(int) $row['enabled'] || (int) $row['auth_revision']!==$this->actor->revision || $row['role']!==$this->actor->role) {
            throw new ItaHttpError(403,'Quyền đã thay đổi. Vui lòng đăng nhập lại.');
        }
    }

    private function insert(array $values): void
    {
        $values = ['store_id'=>$this->actor->storeId]+$values+['created_by'=>$this->actor->id,'updated_by'=>$this->actor->id];
        $columns = implode(',',array_keys($values));
        $this->db->execute('INSERT INTO ita_assets ('.$columns.') VALUES ('.implode(',',array_fill(0,count($values),'?')).')',
            str_repeat('s',count($values)),array_values($values));
        $id = (int) $this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
        (new ItaAuditService($this->db,$this->actor))->record('asset.create','asset',$id,['after'=>$values]);
    }
}
