<?php
/** Query-contract double only: never connect to or execute SQL against a DB. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks = 0;
function statCheck(bool $ok): void { global $checks; if (!$ok) { throw new RuntimeException('Check '.($checks+1)); } $checks++; }
class StatisticsDb extends ItaDb
{
    public array $reads = [];
    public int $groups = 41;
    public function __construct() {}
    public function fetchOne(string $sql, string $types = '', array $params = []): ?array
    {
        statCheck(strlen($types) === count($params));
        $this->reads[] = [$sql,$types,$params];
        if (str_starts_with($sql,'SELECT * FROM ita_assets')) { return ['id'=>7,'department_id'=>10]; }
        if (str_contains($sql,'COUNT(*) total')) { return ['total'=>$this->groups,'ticket_count'=>90,'cost'=>'9999999999999999.99']; }
        throw new RuntimeException('Unexpected query');
    }
    public function fetchAll(string $sql, string $types = '', array $params = []): array
    {
        statCheck(strlen($types) === count($params));
        $this->reads[] = [$sql,$types,$params];
        return [];
    }
}
foreach (['admin_it','system_manager','support_technician'] as $role) {
    $db = new StatisticsDb();
    $actor = new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'username'=>'test','fullname'=>'Test'],[10,12]);
    $objects = new ItaTicketObjects($db,$actor);
    foreach ([1=>0,2=>20,3=>40,999=>40] as $requested=>$offset) {
        $db->reads = [];
        $data = $objects->statistics(['year'=>'2026','page'=>(string) $requested]);
        statCheck($data['page'] === min(3,$requested) && $data['total'] === 41 && $data['per_page'] === 20);
        statCheck($data['summary'] === ['ticket_count'=>90,'cost'=>'9999999999999999.99']);
        [$count,$list] = $db->reads;
        statCheck($list[2] === [...$count[2],$offset]);
        statCheck(str_ends_with($list[0],'ORDER BY t.asset_id,t.type,t.status LIMIT 20 OFFSET ?'));
        statCheck(str_contains($count[0],'FROM ('.substr($list[0],0,strpos($list[0],' ORDER BY')).') g'));
        statCheck(str_contains($list[0],'a.store_id=? AND a.is_deleted=0'));
        statCheck(str_contains($count[0],'SUM(g.cost)') && !str_contains($list[0],'ita_ticket_events'));
        statCheck(array_slice($count[2],-2) === ['2025-12-31 17:00:00','2026-12-31 17:00:00']);
        statCheck(str_contains($list[0],'a.department_id IN (?,?)') === ($role !== 'admin_it'));
        statCheck(str_contains($list[0],'t.technician_id=?') === ($role === 'support_technician'));
        statCheck($count[2] === array_merge([1],$role==='admin_it'?[]:[10,12],$role==='support_technician'?[111]:[],['2025-12-31 17:00:00','2026-12-31 17:00:00']));
    }
    $db->groups = 0;
    $data = $objects->statistics(['year'=>'2024','page'=>'999']);
    statCheck($data['page'] === 1 && $data['total'] === 0 && end($db->reads)[2][count(end($db->reads)[2])-1] === 0);
    foreach (['0','-1','1 OR 1=1',[], '1000000000'] as $invalid) {
        $before = count($db->reads);
        try { $objects->statistics(['year'=>'2026','page'=>$invalid]); throw new RuntimeException('Expected rejection'); }
        catch (ItaHttpError $error) { statCheck($error->status === 422 && count($db->reads) === $before); }
    }
}
$db = new StatisticsDb();
$actor = new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>'system_manager','auth_revision'=>1,'username'=>'test','fullname'=>'Test'],[]);
(new ItaTicketObjects($db,$actor))->statistics(['year'=>'2026']);
statCheck(str_contains($db->reads[0][0],'AND 1=0') && str_contains($db->reads[1][0],'AND 1=0'));
$db = new StatisticsDb();
$actor = new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>'system_manager','auth_revision'=>1,'username'=>'test','fullname'=>'Test'],[10]);
(new ItaTicketObjects($db,$actor))->statistics(['year'=>'2024','asset_id'=>'7','page'=>'2']);
statCheck($db->reads[1][2] === [1,10,7,'2023-12-31 17:00:00','2024-12-31 17:00:00']);
statCheck(str_contains($db->reads[1][0],'AND t.asset_id=?') && $db->reads[2][2] === [...$db->reads[1][2],20]);
$db = new StatisticsDb();
$actor = new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>'system_manager','auth_revision'=>1,'username'=>'test','fullname'=>'Test'],[12]);
try { (new ItaTicketObjects($db,$actor))->statistics(['year'=>'2026','asset_id'=>'7']); throw new RuntimeException('Expected scope rejection'); }
catch (ItaHttpError $error) { statCheck($error->status === 403 && count($db->reads) === 1); }
echo "$checks statistics query-contract checks passed; no DB.\n";
