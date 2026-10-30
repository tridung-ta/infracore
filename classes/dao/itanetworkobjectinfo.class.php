<?php
class ItaNetworkObjectInfo
{
    public static function version(array $row): string
    {
        $fields=['id','source_asset_id','target_asset_id','link_type','source_port','target_port','capacity_label','note','is_deleted','updated_at'];
        $values=[];
        foreach ($fields as $key) { $values[$key]=(string)($row[$key] ?? ''); }
        return hash('sha256',json_encode($values,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    }

    public static function row(array $row): array
    {
        $result=[];
        foreach (['id','source_asset_id','target_asset_id'] as $key) { $result[$key]=(int)$row[$key]; }
        foreach (['link_type','source_port','target_port','capacity_label','note','source_name','target_name'] as $key) { $result[$key]=(string)($row[$key] ?? ''); }
        return $result+['version'=>self::version($row)];
    }
}
