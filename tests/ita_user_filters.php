<?php
/** Query-contract tests only; never connects to DB. */
require __DIR__.'/ita_phase14_unit.php';
class FilterDb extends ItaDb
{
    public array $queries=[];
    public function fetchOne(string $sql,string $types='',array $params=[]): ?array
    { $this->queries[]=[$sql,$types,$params];return ['n'=>41]; }
    public function fetchAll(string $sql,string $types='',array $params=[]): array
    { $this->queries[]=[$sql,$types,$params];return []; }
}
$checks=0;
function filterCheck(bool $ok): void { global $checks;if(!$ok)throw new RuntimeException('Filter assertion failed');$checks++; }
$db=new FilterDb();$dao=new ItaUserObjects($db);$owner=actor14(ItaPermissions::KEYS,1,'admin_it');
$result=$dao->listing($owner,99,['q'=>"a%_!'",'access_status'=>'allowed','department_id'=>'10']);
filterCheck($result['page']===3);
[$count,$types,$params]=$db->queries[0];[$rows,$rowTypes,$rowParams]=$db->queries[1];
filterCheck(substr_count($count,'?')===strlen($types)&&strlen($types)===count($params));
filterCheck($params===[1,"%a!%!_!!'%","%a!%!_!!'%",10]);
filterCheck(!str_contains($count,"a%_!'"));
filterCheck(substr($count,strpos($count,' FROM '))===substr($rows,strpos($rows,' FROM '),strpos($rows,' ORDER BY ')-strpos($rows,' FROM ')));
filterCheck(str_contains($rows,'s.active=1')&&str_contains($rows,'s.store_id=u.store_id'));
filterCheck($rowParams===[...$params,40]&&$rowTypes===$types.'i');
foreach(['not_allowed','locked',''] as $status){$dao->listing($owner,1,['access_status'=>$status]);filterCheck(true);}
foreach([['access_status'=>'forged'],['department_id'=>'-1'],['q'=>[]]] as $bad){try{$dao->listing($owner,1,$bad);throw new RuntimeException('Expected 422');}catch(ItaHttpError $e){filterCheck($e->status===422);}}
echo "$checks account filter contract checks passed; no DB.\n";
