<?php
class ItaForecastRules
{
    public const MAX_ROWS=1000;
    public const MAX_ASSETS=2000;

    public static function access(ItaUserObjectInfo $actor): void
    {
        if (!in_array($actor->role,['admin_it','system_manager'],true)) {
            throw new ItaHttpError(403,'Bạn không có quyền xem ngân sách.');
        }
    }

    public static function year(mixed $value): int
    {
        $year=ItaInput::positiveInt($value);
        $now=self::currentYear();
        if ($year<$now || $year>2200) { throw new ItaHttpError(422,'Năm dự báo phải từ năm hiện tại đến 2200.'); }
        return $year;
    }

    public static function currentYear(): int { return (int)(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y'); }

    public static function parameter(mixed $value,int $maximum): ?string
    {
        if ($value===null || $value==='') { return null; }
        $units=ItaForecastMoney::units($value,6);
        if (strlen($units)>strlen((string)($maximum*1000000)) || (int)$units>$maximum*1000000) {
            throw new ItaHttpError(422,'Tham số vượt giới hạn.');
        }
        return (string)$value;
    }

    /** Score OR end-of-year age, counted once. Unknown eligibility is never a zero. */
    public static function replacement(array $asset,int $year): ?bool
    {
        if (($asset['tier'] ?? '')==='replace') { return true; }
        $today=(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        if (ItaLifecycleRules::age($asset['purchase_date'] ?? null,$today)===null) { return null; }
        $age=ItaLifecycleRules::age($asset['purchase_date'] ?? null,$year.'-12-31');
        $life=$asset['lifespan_years'] ?? null;
        if ($age!==null && $life!==null && $life>0 && $age>=(float)$life) { return true; }
        if ($age===null || !$life || ($asset['score_raw'] ?? null)===null) { return null; }
        return false;
    }

    /** Each row is one department/category; partial subtotals are explicit, not a full budget. */
    public static function row(array $group,array $config,int $headcount): array
    {
        $missing=$group['missing'];$price=$config['price'] ?? null;
        $validPrice=$price!==null;
        $replacement=null;
        if ($group['unknown_replacement']>0) { $missing[]='Thiếu dữ liệu đánh giá thay thế'; }
        if ($group['replacement_count']===0 && $group['unknown_replacement']===0) { $replacement='0.00'; }
        elseif ($validPrice && $group['unknown_replacement']===0) {
            $replacement=ItaForecastMoney::format(ItaForecastMoney::multiply(ItaForecastMoney::units($price),(string)$group['replacement_count']));
        } elseif (!$validPrice) { $missing[]='Thiếu giá thay thế'; }
        $maintenance=null;
        if ($group['asset_count']===0) { $maintenance='0.00'; }
        elseif ($group['history_count']>0) {
            if ($group['history_missing']>0) { $missing[]='Thiếu chi phí bảo trì'; }
            else { $maintenance=ItaForecastMoney::format(ItaForecastMoney::divide($group['history_cents'],3)); }
        } elseif ($validPrice && ($config['maintenance_percent'] ?? null)!==null) {
            $base=ItaForecastMoney::multiply(ItaForecastMoney::units($price),(string)$group['asset_count']);
            $maintenance=ItaForecastMoney::format(ItaForecastMoney::divide(ItaForecastMoney::multiply($base,ItaForecastMoney::units($config['maintenance_percent'],6)),100000000));
        } else { $missing[]='Thiếu chi phí bảo trì hoặc tỷ lệ dự phòng/đơn giá đã cấu hình'; }
        $growth=null;$quantity=null;
        if ($headcount<0) { $missing[]='Chưa lập kế hoạch nhân sự'; }
        elseif ($headcount===0) { $quantity='0';$growth='0.00'; }
        elseif (($config['units_per_person'] ?? null)===null) { $missing[]='Thiếu định mức thiết bị/người'; }
        else {
            $quantity=ItaForecastMoney::divide(ItaForecastMoney::multiply((string)$headcount,ItaForecastMoney::units($config['units_per_person'],6)),1000000,'ceil');
            if ($quantity==='0') { $growth='0.00'; }
            elseif (!$validPrice) { $missing[]='Thiếu giá thay thế cho mua thêm'; }
            else { $growth=ItaForecastMoney::format(ItaForecastMoney::multiply($quantity,ItaForecastMoney::units($price))); }
        }
        return $group+['replacement'=>$replacement,'maintenance'=>$maintenance,'growth'=>$growth,'growth_quantity'=>$quantity,
            'inputs'=>$config+['headcount'=>$headcount],'issues'=>array_values(array_unique($missing))];
    }

    public static function summary(array $rows): array
    {
        $parts=[];
        foreach (['replacement','maintenance','growth'] as $key) {
            $parts[$key]=ItaForecastMoney::total(array_column(array_filter($rows,fn($r)=>$r[$key]!==null),$key));
        }
        $subtotal=ItaForecastMoney::total(array_values($parts));
        $reserve=ItaForecastMoney::format(ItaForecastMoney::divide(ItaForecastMoney::units($subtotal),10));
        return $parts+['subtotal'=>$subtotal,'contingency'=>$reserve,'contingency_percent'=>'10','total'=>ItaForecastMoney::total([$subtotal,$reserve]),
            'complete'=>!array_filter($rows,fn($r)=>(bool)$r['issues'])];
    }
}
