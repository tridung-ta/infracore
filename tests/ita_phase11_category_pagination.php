<?php
/** Contract doubles only. No private config, DB connection or SQL execution. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$checks=0;
function categoryCheck(bool $ok): void {global $checks;if(!$ok)throw new RuntimeException('Check '.($checks+1));$checks++;}
function categoryActor(string $role): ItaUserObjectInfo {return new ItaUserObjectInfo(['id'=>111,'store_id'=>1,'role'=>$role,'auth_revision'=>1,'username'=>'qa','fullname'=>'QA'],[10]);}
class CategoryPageDb extends ItaDb
{
    public array $reads=[];
    public int $total=41,$legacyCount=1;
    public function __construct(){}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        categoryCheck(strlen($types)===count($params));$this->reads[]=[$sql,$types,$params];
        if(str_starts_with($sql,'SELECT COUNT(*) n FROM ita_asset_categories'))return ['n'=>$this->total];
        throw new RuntimeException('Unexpected query');
    }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    {
        categoryCheck(strlen($types)===count($params));$this->reads[]=[$sql,$types,$params];
        return array_fill(0,str_contains($sql,'LIMIT 501')?$this->legacyCount:1,['id'=>2,'code'=>'QA','name'=>'QA','status'=>'inactive']);
    }
}
foreach(['admin_it','system_manager','support_technician'] as $role){
    $db=new CategoryPageDb();$service=new ItaCategoryService($db,categoryActor($role));
    categoryCheck($service->listing()[0]['status']==='inactive'&&str_contains($db->reads[0][0],'LIMIT 501'));
    $db->legacyCount=501;
    try{$service->listing();throw new RuntimeException('Expected422');}catch(ItaHttpError $e){categoryCheck($e->status===422);}
    if($role!=='admin_it'){
        $db->reads=[];try{$service->paginated(['paged'=>'1'],1);throw new RuntimeException('Expected403');}catch(ItaHttpError $e){categoryCheck($e->status===403&&$db->reads===[]);}continue;
    }
    foreach([1=>0,2=>20,3=>40,999=>40] as $page=>$offset){
        $db->reads=[];$data=$service->paginated(['paged'=>'1','q'=>' %_! test '],$page);
        categoryCheck($data['page']===min(3,$page)&&$data['total']===41&&$data['per_page']===20);
        categoryCheck($db->reads[0][2]===[1,'%!%!_!! test%','%!%!_!! test%']);
        categoryCheck($db->reads[1][2]===[...$db->reads[0][2],$offset]);
        categoryCheck(str_contains($db->reads[0][0],"code LIKE ? ESCAPE '!' OR name LIKE ? ESCAPE '!'"));
        categoryCheck(str_ends_with($db->reads[1][0],'ORDER BY name,id LIMIT 20 OFFSET ?'));
        $row=$data['rows'][0];$version=$row['version'];unset($row['version']);categoryCheck($version===ItaAssetService::version($row)&&$row['status']==='inactive');
    }
    foreach([0,20,21,40] as $count){$db->total=$count;$data=$service->paginated(['paged'=>'1'],999);categoryCheck($data['page']===max(1,(int)ceil($count/20)));}
    foreach([['paged'=>[]],['paged'=>'0'],['paged'=>'1','q'=>[]],['paged'=>'1','q'=>str_repeat('a',101)],['paged'=>'1','q'=>"bad\0"]] as $input){
        $db->reads=[];try{$service->paginated($input,1);throw new RuntimeException('Expected422');}catch(ItaHttpError $e){categoryCheck($e->status===422&&$db->reads===[]);}
    }
}
class CategoryVersionDb extends ItaDb
{
    public array $writes=[];
    public bool $rolledBack=false,$committed=false;
    public array $row=['id'=>2,'code'=>'QA','name'=>'QA','status'=>'inactive'];
    public function __construct(){}
    public function beginTransaction(): void {}
    public function commit(): void {$this->committed=true;}
    public function rollBack(): void {$this->rolledBack=true;}
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    {
        categoryCheck($types==='ii'&&$params===[1,2]&&str_ends_with($sql,'FOR UPDATE'));return $this->row;
    }
    public function execute(string $sql,string $types='',array $params=[]): int
    {
        categoryCheck(strlen($types)===count($params));$this->writes[]=[$sql,$types,$params];return 1;
    }
}
$db=new CategoryVersionDb();$service=new ItaCategoryService($db,categoryActor('admin_it'));
$input=['id'=>'2','code'=>'QA','name'=>'Updated','status'=>'inactive','version'=>'stale'];
try{$service->save($input);throw new RuntimeException('Expected409');}catch(ItaHttpError $e){categoryCheck($e->status===409&&$db->writes===[]&&$db->rolledBack);}
$input['version']=ItaAssetService::version($db->row);$service->save($input);
categoryCheck($db->committed&&count($db->writes)===2&&str_starts_with($db->writes[0][0],'UPDATE ita_asset_categories')&&str_contains($db->writes[1][0],'ita_audit_logs'));
echo "$checks category query-contract checks passed; no DB.\n";
