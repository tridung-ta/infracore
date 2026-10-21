<?php
class ItaDashboardObjectInfo
{
    public static function inventory(array $a): array
    {
        return [$a['asset_code'],$a['name'],$a['category_name'] ?? 'Chưa ghi nhận',$a['department_name'] ?? 'Chưa ghi nhận',
            ['in_use'=>'Đang sử dụng','maintenance'=>'Bảo trì','broken'=>'Hỏng','retired'=>'Thanh lý'][$a['status']],$a['purchase_date'] ?? 'Chưa ghi nhận',$a['warranty_end'] ?? 'Chưa ghi nhận'];
    }

    public static function maintenance(array $a,string $today): array
    {
        return [$a['asset_code'],$a['name'],$a['department_name'] ?? 'Chưa ghi nhận',$a['maintenance_due'],$a['maintenance_due']<$today?'Quá hạn':'Sắp đến hạn'];
    }
}
