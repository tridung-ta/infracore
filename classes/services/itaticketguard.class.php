<?php
class ItaTicketGuard
{
    public const LATEST = " ORDER BY COALESCE((SELECT MAX(e.created_at) FROM ita_ticket_events e WHERE e.store_id=t.store_id AND e.ticket_id=t.id),t.opened_at) DESC,(SELECT MAX(e.id) FROM ita_ticket_events e WHERE e.store_id=t.store_id AND e.ticket_id=t.id) DESC,t.id DESC LIMIT 1";
    /** Caller must hold the asset row lock before consulting tickets. */
    public static function pending(ItaDb $db, int $storeId, int $assetId): bool
    {
        return $db->fetchOne("SELECT id FROM ita_tickets WHERE store_id=? AND asset_id=? AND status='pending' LIMIT 1 FOR UPDATE",'ii',[$storeId,$assetId])!==null;
    }

    public static function clear(ItaDb $db, int $storeId, int $assetId): void
    {
        if (self::pending($db,$storeId,$assetId)) {
            throw new ItaHttpError(409,'Thiết bị còn phiếu pending. Cần xử lý phiếu trước khi giao mới, chuyển bộ phận, thanh lý, xóa hoặc rời bảo trì.');
        }
    }

    public static function waiting(ItaDb $db, int $storeId, array $asset): bool
    {
        if ($asset['status']!=='maintenance' || $asset['current_user_id']!==null) {
            return false;
        }
        if ($db->fetchOne("SELECT id FROM ita_tickets WHERE store_id=? AND asset_id=? AND status='pending' LIMIT 1",'ii',[$storeId,$asset['id']])) {
            return false;
        }
        $last = $db->fetchOne('SELECT t.status FROM ita_tickets t WHERE t.store_id=? AND t.asset_id=?'.self::LATEST,'ii',[$storeId,$asset['id']]);
        return $last && $last['status']==='resolved'
            && !$db->fetchOne('SELECT id FROM ita_assignments WHERE store_id=? AND asset_id=? AND returned_at IS NULL LIMIT 1','ii',[$storeId,$asset['id']]);
    }
}
