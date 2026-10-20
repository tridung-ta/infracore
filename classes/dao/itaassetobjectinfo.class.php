<?php
class ItaAssetObjectInfo
{
    public function __construct(private array $row)
    {
    }

    public function presentation(): array
    {
        $r = $this->row;
        return ['id'=>(int) $r['id'],'code'=>$r['asset_code'],'name'=>$r['name'],'serial'=>$r['serial_no'] ?? 'Chưa ghi nhận',
            'category'=>$r['category_name'],'department'=>$r['department_name'] ?? 'Chưa phân bổ','status'=>$r['status'],
            'warranty'=>$r['warranty_end'] ? (new DateTimeImmutable($r['warranty_end']))->format('d/m/Y') : 'Chưa ghi nhận',
            'specs'=>$r['specs'] ?? 'Chưa ghi nhận','location'=>$r['location'] ?? 'Chưa ghi nhận',
            'maintenance_overdue'=>ItaAssignmentRules::overdue($r),'next_maintenance_due'=>ItaMaintenanceSchedule::effective($r)];
    }
}
