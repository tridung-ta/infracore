<?php
class ItaMaintenanceSchedule
{
    public static function initial(ItaDb $db, int $storeId, array $values, ?array $before = null): array
    {
        $changed = $before && (($before['purchase_date'] ?? null)!==($values['purchase_date'] ?? null) || (int) $before['category_id']!==(int) $values['category_id']);
        if (!empty($before['last_maintenance_at']) || (!empty($before['next_maintenance_due']) && !$changed)) {
            return $values;
        }
        $category = $db->fetchOne('SELECT maintenance_cycle_months FROM ita_asset_categories WHERE store_id=? AND id=?','ii',[$storeId,$values['category_id']]);
        $row = $values+['maintenance_cycle_months'=>$category['maintenance_cycle_months'] ?? null];
        return $values+['next_maintenance_due'=>self::effective($row)];
    }

    /** Read fallback matches next(): MariaDB DATE_ADD clamps the target month day. */
    public const SQL = 'COALESCE(a.next_maintenance_due,DATE(DATE_ADD(COALESCE(CONVERT_TZ(a.last_maintenance_at,\'+00:00\',\'+07:00\'),a.purchase_date),INTERVAL COALESCE(c.maintenance_cycle_months,6) MONTH)))';

    public static function next(string $date, ?int $months): string
    {
        $date = ItaInput::date($date);
        $months ??= 6;
        if ($months<1 || $months>1200) {
            throw new ItaHttpError(422,'Chu kỳ bảo trì phải từ 1 đến 1200 tháng.');
        }
        $first = $date->modify('first day of this month')->modify('+'.$months.' months');
        $day = min((int) $date->format('d'),(int) $first->format('t'));
        $result = $first->setDate((int) $first->format('Y'),(int) $first->format('m'),$day)->format('Y-m-d');
        if (strlen($result)!==10) {
            throw new ItaHttpError(422,'Lịch bảo trì vượt giới hạn ngày.');
        }
        return $result;
    }

    public static function effective(array $row): ?string
    {
        if (!empty($row['next_maintenance_due'])) {
            return $row['next_maintenance_due'];
        }
        $last = $row['last_maintenance_at'] ?? null;
        $base = $last ? (new DateTimeImmutable($last,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('Y-m-d') : ($row['purchase_date'] ?? null);
        return $base ? self::next($base,isset($row['maintenance_cycle_months'])?(int) $row['maintenance_cycle_months']:null) : null;
    }
}
