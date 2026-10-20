<?php
class ItaTicketObjectInfo
{
    public static function present(array $row): array
    {
        return $row+['version'=>ItaAssetService::version($row)];
    }
}
