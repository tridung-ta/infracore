<?php
class ItaLicenseObjectInfo
{
    public static function present(array $row): array
    {
        $hasKey = !empty($row['license_key_enc']);
        $version = ItaAssetService::version($row);
        unset($row['license_key_enc']);
        return $row+['has_key'=>$hasKey,'key_mask'=>$hasKey?'••••••••':'Chưa có key',
            'version'=>$version,'alerts'=>ItaLicenseRules::alert($row['expire_date'],(int) $row['used_seats'],(int) $row['total_seats'])];
    }
}
