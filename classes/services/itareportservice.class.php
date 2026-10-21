<?php
class ItaReportService
{
    public function __construct(private ItaDb $db,private ItaUserObjectInfo $actor,private string $secret) {}

    /** A single read transaction and one VN timestamp for every query in a response. */
    public function read(callable $work): array
    {
        if (!in_array(strtoupper(str_replace('_','-',$this->db->transactionIsolation())),['REPEATABLE-READ','SERIALIZABLE'],true)) {
            throw new ItaHttpError(503,'Báo cáo cần REPEATABLE READ hoặc SERIALIZABLE để đọc nhất quán.');
        }
        $this->db->beginTransaction();
        try { $result=$work();$this->db->commit();return $result; }
        catch (Throwable $e) { $this->db->rollBack();throw $e; }
    }

    public static function now(): string
    {
        return (new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d\TH:i:sP');
    }

    public function document(array $input,string $asOf): array
    {
        $type=ItaInput::text($input['report'] ?? 'inventory',20);
        ItaDashboardRules::access($this->actor,$type==='maintenance');
        ItaDashboardRules::limit($type,'xlsx');$today=substr($asOf,0,10);$source='Số liệu hiện tại';$missing=0;
        if ($type==='budget') {
            $dao=new ItaForecastObjects($this->db,$this->actor);
            $budget=!empty($input['plan_id'])?$dao->saved(ItaInput::positiveInt($input['plan_id']),empty($input['category_id'])?0:ItaInput::positiveInt($input['category_id'])):$dao->calculate($input);
            if (!empty($budget['saved']) && ((!empty($input['department_id']) && (int)$input['department_id']!==(int)$budget['department_id']) || (!empty($input['year']) && (int)$input['year']!==(int)$budget['year']))) {
                throw new ItaHttpError(422,'Snapshot không khớp bộ phận/năm đã chọn. Hãy sửa bộ lọc.');
            }
            if (empty($budget['saved'])) { $budget['as_of']=$asOf; }
            $table=ItaForecastExport::table($budget);$count=count($budget['rows']);
            $source=!empty($budget['saved'])?'Snapshot #'.$budget['plan_id'].' — tính tại '.$budget['as_of']:'Dự báo hiện tại — không phải snapshot đã chốt';
            return ['report'=>$type,'as_of'=>$asOf,'source'=>$source,'headers'=>array_shift($table),'rows'=>$table,'data_count'=>$count,'budget'=>$budget];
        }
        $dao=new ItaDashboardObjects($this->db,$this->actor,$asOf);$d=$type==='inventory'?['assets'=>$dao->assets($input),'scores'=>[],'config'=>null]:$dao->dataset($input,$type==='maintenance');$rows=[];
        $headers=match($type) {
            'inventory'=>['Mã','Thiết bị','Loại','Bộ phận','Trạng thái','Ngày mua','Bảo hành đến'],
            'maintenance'=>['Mã','Thiết bị','Bộ phận','Hạn bảo trì','Tình trạng'],
            'lifecycle'=>['Mã','Thiết bị','Bộ phận','Ngày mua','Tuổi thọ (năm)','Điểm thay thế','Nhóm'],
        };
        foreach ($d['assets'] as $a) {
            if ($type==='inventory') { $rows[]=ItaDashboardObjectInfo::inventory($a);continue; }
            if ($a['status']==='retired') { continue; }
            if ($type==='maintenance') {
                if (!$a['maintenance_due']) { $missing++;continue; }
                if ($a['maintenance_due']<=ItaInput::date($today)->modify('+30 days')->format('Y-m-d')) { $rows[]=ItaDashboardObjectInfo::maintenance($a,$today); }
            } elseif (isset($d['scores'][$a['id']])) {
                $s=$d['scores'][$a['id']];$short=ItaDashboardRules::shortLife($s,$today);
                if ($short===null) { $missing++;continue; }
                if ($short) { $rows[]=[$a['asset_code'],$a['name'],$a['department_name'] ?? 'Chưa ghi nhận',$s['purchase_date'],$s['lifespan_years'],$s['score_display'] ?? 'Chưa đủ dữ liệu',['replace'=>'Thay thế','watch'=>'Theo dõi','normal'=>'Bình thường','missing'=>'Chưa đủ dữ liệu'][$s['tier']]]; }
            }
        }
        return ['report'=>$type,'as_of'=>$asOf,'source'=>$source,'headers'=>$headers,'rows'=>$rows,'data_count'=>count($rows),'missing'=>$missing,'using_defaults'=>$d['config']['using_defaults'] ?? false];
    }

    private function signature(array $doc,array $input): string
    {
        if (strlen($this->secret)<32) { throw new ItaHttpError(503,'Chưa có khóa xác thực báo cáo.'); }
        $filters=array_intersect_key($input,array_flip(['report','department_id','category_id','year','plan_id']));ksort($filters);
        return hash_hmac('sha256',json_encode([$this->actor->id,$this->actor->storeId,$this->actor->revision,$this->actor->departmentIds,$filters,$doc],JSON_THROW_ON_ERROR),$this->secret);
    }

    public function preview(array $input,int $page): array
    {
        return $this->read(function() use ($input,$page) {
            $doc=$this->document($input,self::now());$token=$this->signature($doc,$input);
            $visible=$doc;unset($visible['budget']);
            return array_replace($visible,ItaDashboardRules::paginate($doc['rows'],$page))+['token'=>$token,'data_count'=>$doc['data_count']];
        });
    }

    public function export(array $input): array
    {
        ItaDashboardRules::access($this->actor);
        $asOf=ItaInput::text($input['as_of'] ?? '',25);
        $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP',$asOf);
        if (!$date || $date->format('Y-m-d\TH:i:sP')!==$asOf || !str_ends_with($asOf,'+07:00') || abs(time()-$date->getTimestamp())>900) {
            throw new ItaHttpError(409,'Bản xem trước hết hạn. Hãy tải lại báo cáo trước khi xuất.');
        }
        $doc=$this->read(fn()=>$this->document($input,$asOf));$format=ItaInput::text($input['format'] ?? '',5);
        $limit=ItaDashboardRules::limit($doc['report'],$format);
        if ($doc['data_count']>$limit) { throw new ItaHttpError(422,'Báo cáo vượt '.$limit.' dòng. Thu hẹp bộ lọc; không cắt ngầm.'); }
        if (!hash_equals($this->signature($doc,$input),ItaInput::text($input['token'] ?? '',64))) { throw new ItaHttpError(409,'Số liệu hoặc bộ lọc đã đổi. Hãy tải lại bản xem trước.'); }
        $title=['inventory'=>'Tồn kho','maintenance'=>'Sắp bảo trì','lifecycle'=>'Vòng đời ngắn','budget'=>'Ngân sách'][$doc['report']];
        $table=[['Báo cáo',$title,'Số liệu tại',$asOf],['Nguồn',$doc['source']],['Thiếu dữ liệu',$doc['missing'] ?? 0],$doc['headers'],...$doc['rows']];
        $width=max(array_map('count',$table));
        $bytes=$format==='xlsx'?ItaExcel::write(array_pad(['Báo cáo hạ tầng CNTT'],$width,''),$table):ItaForecastPdf::render($table,'Báo cáo hạ tầng CNTT');
        $this->audit($doc,$format);
        return ['bytes'=>$bytes,'format'=>$format,'report'=>$doc['report']];
    }

    /** Current locking reads after render: revocation or audit failure prevents all file bytes. */
    private function audit(array $doc,string $format): void
    {
        $this->db->beginTransaction();
        try {
            $native=$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->actor->storeId,$this->actor->id]);
            if (!$native) { throw new ItaHttpError(403,'Tài khoản đã ngừng hoạt động. Hãy đăng nhập lại.'); }
            $r=$this->db->fetchOne('SELECT role,enabled,auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=? FOR UPDATE','ii',[$this->actor->storeId,$this->actor->id]);
            if (!$r || !(int)$r['enabled'] || $r['role']!==$this->actor->role || (int)$r['auth_revision']!==$this->actor->revision) { throw new ItaHttpError(403,'Quyền đã thay đổi. Hãy đăng nhập lại.'); }
            if (!$this->actor->isAdmin()) {
                $rows=$this->db->fetchAll('SELECT department_id FROM ita_user_scopes WHERE store_id=? AND user_id=? AND active=1 ORDER BY department_id FOR UPDATE','ii',[$this->actor->storeId,$this->actor->id]);
                $current=array_map('intval',array_column($rows,'department_id'));$known=$this->actor->departmentIds;sort($current);sort($known);
                if ($current!==$known) { throw new ItaHttpError(403,'Phạm vi đã thay đổi. Hãy tải lại báo cáo.'); }
            }
            (new ItaAuditService($this->db,$this->actor))->record('report.export','report',0,['after'=>['report_type'=>$doc['report'],'format'=>$format,'total_rows'=>$doc['data_count'],'as_of'=>$doc['as_of'],'snapshot_hash'=>hash('sha256',json_encode($doc,JSON_THROW_ON_ERROR))]]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack();throw $e; }
    }
}
