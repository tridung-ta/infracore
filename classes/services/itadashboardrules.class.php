<?php
/** Shared presentation rules; lifecycle scores come from the existing DECIMAL query. */
class ItaDashboardRules
{
    public static function access(ItaUserObjectInfo $actor,bool $maintenance=false): void
    {
        if ($actor->capabilitiesEnabled) {
            if (!$actor->can('dashboard.read') && !$actor->can('alert.read') && !$actor->can('report.read') && !($maintenance && $actor->can('report.maintenance'))) {
                throw new ItaHttpError(403,'Bạn chưa được cấp quyền xem dữ liệu phân tích này.');
            }
            return;
        }
        if (!in_array($actor->role,['admin_it','system_manager'],true) && !($maintenance && $actor->role==='support_technician')) {
            throw new ItaHttpError(403,'Bạn chỉ được xem bảng bảo trì trong phạm vi phụ trách.');
        }
    }

    public static function limit(string $report,string $format): int
    {
        if (!in_array($report,['inventory','maintenance','lifecycle','budget'],true) || !in_array($format,['xlsx','pdf'],true)) {
            throw new ItaHttpError(422,'Loại báo cáo hoặc định dạng không hợp lệ.');
        }
        return $format==='pdf'?500:($report==='inventory'?5000:1000);
    }

    public static function shortLife(array $row,string $today): ?bool
    {
        if (ItaLifecycleRules::age($row['purchase_date'] ?? null,$today)===null || empty($row['lifespan_years'])) { return null; }
        $months=(float)$row['lifespan_years']*12;
        if ($months<1 || floor($months)!==$months || $months>1200) { return null; }
        $end=ItaMaintenanceSchedule::next($row['purchase_date'],(int)$months);
        return $end<=ItaMaintenanceSchedule::next($today,12);
    }

    public static function paginate(array $rows,int $page,int $size=20): array
    {
        $total=count($rows);$page=max(1,min($page,max(1,(int)ceil($total/$size))));
        return ['rows'=>array_slice($rows,($page-1)*$size,$size),'page'=>$page,'per_page'=>$size,'total'=>$total];
    }

    public static function alerts(array $assets,array $licenses,string $today): array
    {
        $rows=[];$until=ItaInput::date($today)->modify('+30 days')->format('Y-m-d');
        foreach ($assets as $a) {
            if ($a['status']==='retired') { continue; }
            $add=function(string $reason,string $level,string $title) use (&$rows,$a) {
                $rows[]=['entity_type'=>'asset','entity_id'=>(int)$a['id'],'reason'=>$reason,'level'=>$level,'code'=>$a['asset_code'],'name'=>$a['name'],'title'=>$title];
            };
            if ($a['status']==='broken') { $add('broken','critical','Thiết bị hỏng'); }
            $due=$a['maintenance_due'] ?? null;
            if ($due && $due<=$until) { $add($due<$today?'maintenance_overdue':'maintenance_due',$due<$today?'critical':'warning','Bảo trì: '.$due); }
            if (!empty($a['warranty_end']) && $a['warranty_end']<=$until) { $add('warranty','warning','Bảo hành đến: '.$a['warranty_end']); }
            if (!empty($a['awaiting_ready'])) { $add('awaiting_ready','warning','Chờ Admin xác nhận sẵn sàng'); }
        }
        foreach ($licenses as $l) {
            $alert=ItaLicenseRules::alert($l['expire_date'],(int)$l['used_seats'],(int)$l['total_seats'],$today);
            foreach (['license_overage'=>$alert['usage_level'],'license_expiry'=>$l['status']==='active'?$alert['expiry_level']:'none'] as $reason=>$level) {
                if ($level!=='none') { $rows[]=['entity_type'=>'license','entity_id'=>(int)$l['id'],'reason'=>$reason,'level'=>$level,'code'=>'LIC-'.$l['id'],'name'=>$l['software_name'],'title'=>$reason==='license_overage'?'Giấy phép vượt số chỗ':'Giấy phép hết hạn/sắp hết hạn: '.$l['expire_date']]; }
            }
        }
        usort($rows,fn($a,$b)=>[($a['level']==='critical'?0:1),$a['entity_type'],$a['entity_id'],$a['reason']]<=>[($b['level']==='critical'?0:1),$b['entity_type'],$b['entity_id'],$b['reason']]);
        return $rows;
    }

    public static function groups(array $assets,array $scores,string $group): array
    {
        if (!in_array($group,['category','department','year'],true)) { throw new ItaHttpError(422,'Nhóm biểu đồ không hợp lệ.'); }
        $map=[];
        foreach ($assets as $a) {
            $id=$group==='year'?($a['purchase_date']?substr($a['purchase_date'],0,4):'missing'):($a[$group.'_id'] ?? 'missing');
            $name=$group==='year'?(string)$id:($a[$group.'_name'] ?? 'Chưa ghi nhận');
            if ($id==='missing') { $name='Chưa ghi nhận'; }
            $key=(string)$id;
            $map[$key] ??= ['id'=>$key,'name'=>$name,'total'=>0,'by_status'=>['in_use'=>0,'maintenance'=>0,'broken'=>0,'retired'=>0],'analyzed'=>0,'failed_assets'=>0,'replace'=>0,'missing_scores'=>0];
            $r=&$map[$key];$r['total']++;$r['by_status'][$a['status']]++;
            if (isset($scores[$a['id']])) {
                $s=$scores[$a['id']];$r['analyzed']++;$r['failed_assets']+=(int)$s['repair_count']>0?1:0;
                $r['replace']+=$s['tier']==='replace'?1:0;$r['missing_scores']+=$s['tier']==='missing'?1:0;
            }
            unset($r);
        }
        foreach ($map as &$r) { $r['fault_rate']=$r['analyzed']?$r['failed_assets']/$r['analyzed']:null; }
        return array_values($map);
    }
}
