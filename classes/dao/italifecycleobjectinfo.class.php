<?php
class ItaLifecycleObjectInfo
{
    public static function present(array $row,array $config): array
    {
        $row['id']=(int)$row['id'];$row['repair_count']=(int)$row['repair_count'];
        $row['score_display']=ItaLifecycleRules::display($row['score_raw']===null?null:(string)$row['score_raw'],$config);
        $missing=[];
        if ($row['age_days']===null || (int)$row['age_days']<0) { $missing[]='Ngày mua thiếu hoặc ở tương lai'; }
        if ($row['lifespan_years']===null || (float)$row['lifespan_years']<=0) { $missing[]='Chưa cấu hình tuổi thọ loại thiết bị'; }
        if ($row['status']!=='broken' && ($row['purchase_price']===null || (float)$row['purchase_price']<=0)) { $missing[]='Giá mua thiếu hoặc bằng 0'; }
        $row['missing_reasons']=$missing;
        return $row;
    }
}
