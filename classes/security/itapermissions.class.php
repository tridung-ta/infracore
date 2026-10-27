<?php
/** Fixed capability catalogue; feature stays off until an explicitly prepared rollout. */
class ItaPermissions
{
    public const KEYS = [
        'asset.read','asset.create','asset.update','asset.status','asset.note','asset.retire','asset.delete',
        'category.manage','import.read','import.upload','import.confirm','asset.export',
        'assignment.read','assignment.create','assignment.return','assignment.acknowledge','assignment.pdf',
        'license.read','license.manage','license.allocate','license.allocate_user','license.overage','license.reveal',
        'ticket.read','ticket.create','ticket.assign','ticket.process','ticket.cost','ticket.ready','ticket.statistics','ticket.statistics_self',
        'dashboard.read','alert.read','lifecycle.read','forecast.read','forecast.configure','forecast.plan','forecast.export',
        'report.read','report.maintenance','report.export','network.read','network.manage','audit.read','access.read','access.manage','scope.store',
    ];

    public static function enabled(): bool
    {
        return defined('ITA_CAPABILITIES_ENABLED') && ITA_CAPABILITIES_ENABLED === true;
    }

    public static function validate(mixed $input): array
    {
        if (!is_array($input) || count($input)>count(self::KEYS)) {
            throw new ItaHttpError(422,'Danh sách quyền không hợp lệ.');
        }
        foreach ($input as $permission) {
            if (!is_string($permission) || !in_array($permission,self::KEYS,true)) {
                throw new ItaHttpError(422,'Có quyền chức năng không hợp lệ.');
            }
        }
        $keys=array_values(array_unique($input));sort($keys);
        return $keys;
    }

    /** Compatibility presets only when capability mode is off; never an enabled-mode fallback. */
    public static function preset(string $role): array
    {
        if ($role==='admin_it') { return self::KEYS; }
        $common=['asset.read','asset.status','asset.note','assignment.read','assignment.return','assignment.acknowledge','assignment.pdf','ticket.read','ticket.create'];
        if ($role==='support_technician') { return [...$common,'ticket.process','ticket.statistics_self','report.maintenance']; }
        if ($role==='system_manager') {
            return [...$common,'asset.create','asset.update','import.read','import.upload','import.confirm','asset.export',
                'assignment.create','license.read','license.allocate','ticket.assign','ticket.statistics',
                'dashboard.read','alert.read','lifecycle.read','forecast.read','forecast.plan','forecast.export','report.read','report.export','network.read'];
        }
        return [];
    }

    public static function dependencies(array $keys): void
    {
        $requirements=['category.manage'=>['asset.read','scope.store'],'asset.retire'=>['asset.status'],
            'import.upload'=>['import.read'],'import.confirm'=>['import.read'],'asset.export'=>['asset.read'],
            'assignment.read'=>['asset.read'],'ticket.create'=>['asset.read'],
            'assignment.create'=>['assignment.read','asset.read'],'assignment.return'=>['assignment.read','asset.read'],
            'assignment.acknowledge'=>['assignment.read'],'assignment.pdf'=>['assignment.read'],
            'license.allocate_user'=>['license.allocate','scope.store'],'license.overage'=>['license.allocate'],'license.manage'=>['scope.store'],'license.reveal'=>['scope.store'],
            'forecast.configure'=>['forecast.read','scope.store'],'forecast.plan'=>['forecast.read'],'forecast.export'=>['forecast.read'],
            'report.export'=>['report.read'],'network.manage'=>['network.read'],'audit.read'=>['access.read'],'access.manage'=>['access.read'],'access.read'=>['scope.store']];
        foreach ($keys as $key) {
            $read = str_starts_with($key,'asset.') && $key!=='asset.read' ? 'asset.read'
                : (str_starts_with($key,'license.') && $key!=='license.read' ? 'license.read'
                : (str_starts_with($key,'ticket.') && $key!=='ticket.read' ? 'ticket.read' : null));
            foreach ([...($requirements[$key] ?? []),...($read?[$read]:[])] as $required) {
                if (!in_array($required,$keys,true)) { throw new ItaHttpError(422,'Quyền '.$key.' cần thêm '.$required.'.'); }
            }
        }
    }

    /** Exact external GET gates, separate from shared internal read-model helpers. */
    public static function read(ItaUserObjectInfo $actor,string $action,array $query): void
    {
        $map=['asset'=>'asset.read','users'=>'access.read','access'=>'access.manage','audit'=>'audit.read',
            'assignments'=>'assignment.read','assignment-recipients'=>'assignment.create','assignment-pdf'=>'assignment.pdf',
            'licenses'=>'license.read','license'=>'license.read','tickets'=>'ticket.read','ticket'=>'ticket.read','ticket-technicians'=>'ticket.assign',
            'lifecycle'=>'lifecycle.read','lifecycle-detail'=>'lifecycle.read',
            'forecast'=>'forecast.read','forecast-config'=>'forecast.read','forecast-plans'=>'forecast.read','forecast-plan'=>'forecast.read',
            'dashboard'=>'dashboard.read','dashboard-charts'=>'dashboard.read','alerts'=>'alert.read',
            'network'=>'network.read','network-detail'=>'network.read',
            'import-template'=>'import.read','import-preview'=>'import.read','import-errors'=>'import.read','asset-export'=>'asset.export'];
        if (isset($map[$action])) { $actor->requireCapability($map[$action]); }
        elseif ($action==='categories' && array_key_exists('paged',$query)) { $actor->requireCapability('category.manage'); }
        elseif ($action==='ticket-statistics') {
            if (!$actor->can('ticket.statistics') && !$actor->can('ticket.statistics_self')) { throw new ItaHttpError(403,'Bạn chưa được cấp quyền thống kê phiếu.'); }
        } elseif ($action==='report-preview') {
            $actor->requireCapability(($query['report'] ?? '')==='maintenance' && !$actor->can('report.read')?'report.maintenance':'report.read');
        }
    }

    /** Lock the same revision row as the access writer before capability-mode mutations. */
    public static function recheck(ItaDb $db,ItaUserObjectInfo $actor,string $permission): void
    {
        $row=$db->fetchOne('SELECT enabled,auth_revision FROM ita_user_roles WHERE store_id=? AND user_id=? FOR UPDATE','ii',[$actor->storeId,$actor->id]);
        $current=(new ItaUserObjects($db))->current($actor->storeId,$actor->id);
        $before=$actor->departmentIds;$after=$current?->departmentIds ?? [];sort($before);sort($after);
        if (!$row || (int)$row['enabled']!==1 || (int)$row['auth_revision']!==$actor->revision || !$current
            || $current->revision!==$actor->revision || !$current->can($permission) || $current->permissions!==$actor->permissions
            || $after!==$before) {
            throw new ItaHttpError(403,'Quyền chức năng hoặc phạm vi đã thay đổi. Hãy đăng nhập lại.');
        }
    }
}
