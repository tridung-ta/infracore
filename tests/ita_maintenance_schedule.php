<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../classes/security/itahttperror.class.php';
require __DIR__.'/../classes/security/itainput.class.php';
require __DIR__.'/../classes/database/itadb.class.php';
require __DIR__.'/../classes/services/itamaintenanceschedule.class.php';
class ScheduleDb extends ItaDb
{
    public function __construct() {}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        if (!str_contains($sql,'SELECT maintenance_cycle_months') || $types!=='ii' || $params!==[1,2]) {
            throw new RuntimeException('Unexpected scripted query');
        }
        return ['maintenance_cycle_months'=>6];
    }
}
$checks=0;
function scheduleCheck(bool $ok): void { global $checks;if(!$ok)throw new RuntimeException('Schedule check failed');$checks++; }
scheduleCheck(ItaMaintenanceSchedule::next('2023-08-31',6)==='2024-02-29');
scheduleCheck(ItaMaintenanceSchedule::next('2024-08-31',6)==='2025-02-28');
scheduleCheck(ItaMaintenanceSchedule::next('2026-01-31',1)==='2026-02-28');
scheduleCheck(ItaMaintenanceSchedule::effective([])===null);
scheduleCheck(ItaMaintenanceSchedule::effective(['purchase_date'=>'2026-01-31'])==='2026-07-31');
scheduleCheck(ItaMaintenanceSchedule::effective(['purchase_date'=>'2026-01-31','next_maintenance_due'=>'2026-09-01'])==='2026-09-01');
scheduleCheck(ItaMaintenanceSchedule::effective(['last_maintenance_at'=>'2026-01-30 17:00:00','maintenance_cycle_months'=>1])==='2026-02-28');
$db=new ScheduleDb();$before=['category_id'=>2,'purchase_date'=>'2026-01-31','next_maintenance_due'=>'2026-07-31','last_maintenance_at'=>null];
$values=['category_id'=>2,'purchase_date'=>'2026-02-28'];
scheduleCheck(ItaMaintenanceSchedule::initial($db,1,$values,$before)['next_maintenance_due']==='2026-08-28');
$before['last_maintenance_at']='2026-02-01 00:00:00';
scheduleCheck(!array_key_exists('next_maintenance_due',ItaMaintenanceSchedule::initial($db,1,$values,$before)));
echo "$checks pure/scripted maintenance checks passed; no DB.\n";
