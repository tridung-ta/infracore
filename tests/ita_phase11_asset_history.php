<?php
/** Read-contract adapter only; no SQL execution, config or DB connection. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function historyCheck(bool $ok): void { global $checks; if(!$ok)throw new RuntimeException('Check '.($checks+1)); $checks++; }
class HistoryDb extends ItaDb
{
    public array $reads=[];
    public int $total=41;
    public ?array $asset=['id'=>7,'store_id'=>1,'asset_code'=>'QA-7','name'=>'QA','category_name'=>'QA','category_id'=>2,'department_id'=>10,'status'=>'in_use','warranty_end'=>null];
    public function __construct() {}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        historyCheck(strlen($types)===count($params));$this->reads[]=[$sql,$types,$params];
        if(str_starts_with($sql,'SELECT COUNT(*) n FROM ita_audit_logs'))return ['n'=>$this->total];
        if(str_starts_with($sql,'SELECT a.*'))return $this->asset;
        throw new RuntimeException('Unexpected query');
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        historyCheck(strlen($types)===count($params));$this->reads[]=[$sql,$types,$params];
        return [['id'=>41,'action'=>'asset.note','user_id'=>111,'before_json'=>null,'after_json'=>'{}','created_at'=>'2026-10-23 01:00:00']];
    }
}
function historyActor(string $role,array $scope=[10]): ItaUserObjectInfo
{
    return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'fullname'=>'QA','username'=>'qa'],$scope);
}
foreach(['admin_it','system_manager','support_technician'] as $role){
    foreach([1=>0,2=>20,3=>40,999=>40] as $requested=>$offset){
        $db=new HistoryDb();$data=(new ItaAssetObjects($db,historyActor($role)))->detail(7,$requested);
        historyCheck($data['history_pagination']===['page'=>min(3,$requested),'per_page'=>20,'total'=>41]);
        historyCheck($data['history_has_more']===($requested<3));
        historyCheck($db->reads[0][2]===[1,7] && str_contains($db->reads[0][0],'a.is_deleted=0'));
        historyCheck($db->reads[1][2]===[1,7] && $db->reads[2][2]===[1,7,$offset]);
        historyCheck(str_ends_with($db->reads[2][0],"entity_id=? ORDER BY id DESC LIMIT 20 OFFSET ?"));
        historyCheck(str_contains($db->reads[1][0],"store_id=? AND entity_type='asset' AND entity_id=?"));
        historyCheck($data['history'][0]['id']===41 && !array_key_exists('category_name',$data['fields']));
    }
    foreach([0,20,21,40] as $total){
        $db=new HistoryDb();$db->total=$total;$data=(new ItaAssetObjects($db,historyActor($role)))->detail(7,999);
        historyCheck($data['history_pagination']['page']===max(1,(int)ceil($total/20)) && !$data['history_has_more']);
    }
}
foreach(['system_manager','support_technician'] as $role){
    $db=new HistoryDb();
    try{(new ItaAssetObjects($db,historyActor($role,[12])))->detail(7,2);throw new RuntimeException('Expected403');}
    catch(ItaHttpError $e){historyCheck($e->status===403 && count($db->reads)===1);}
}
$db=new HistoryDb();$db->asset=null;
try{(new ItaAssetObjects($db,historyActor('admin_it')))->detail(7,2);throw new RuntimeException('Expected404');}
catch(ItaHttpError $e){historyCheck($e->status===404 && count($db->reads)===1);}
echo "$checks asset history contract checks passed; no DB.\n";
