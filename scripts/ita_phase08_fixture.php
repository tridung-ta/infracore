<?php
/** Synthetic lifecycle preview fixture, no private configuration or database. */
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$config=ItaLifecycleRules::settings([]);$today='2026-10-21';
$seeds=[
 [1,'MOCK-PC-01','Máy tính văn phòng',1,'Máy tính',1,'Công nghệ thông tin','2021-10-21',5,'15000000',3,'0','in_use'],
 [2,'MOCK-NAS-02','Thiết bị lưu trữ',2,'Thiết bị lưu trữ',1,'Công nghệ thông tin','2016-10-21',5,'30000000',4,'12000000','broken'],
 [3,'MOCK-MON-03','Màn hình phòng họp',3,'Màn hình',2,'Vận hành','2023-10-21',6,'5000000',0,'0','in_use'],
 [4,'MOCK-PRN-04','Máy in cần bổ sung hồ sơ',4,'Máy in',2,'Vận hành',null,5,null,0,'0','maintenance'],
 [5,'MOCK-SW-05','Switch dự kiến tiếp nhận',5,'Switch / Router',1,'Công nghệ thông tin','2027-01-01',7,'8000000',0,'0','in_use'],
 [6,'MOCK-PRN-06','Máy in hành chính',4,'Máy in',2,'Vận hành','2021-10-21',5,'10000000',1,'1000000','in_use']
];
$rows=[];
foreach($seeds as $s){
 [$id,$code,$name,$categoryId,$category,$departmentId,$department,$purchase,$life,$price,$repairs,$cost,$status]=$s;
 $age=ItaLifecycleRules::age($purchase,$today);$score=ItaLifecycleRules::score(['age_years'=>$age,'lifespan_years'=>$life,'purchase_price'=>$price,'repair_count'=>$repairs,'repair_cost'=>$cost,'status'=>$status],$config['values']);
 $rows[]=ItaLifecycleObjectInfo::present(['id'=>$id,'asset_code'=>$code,'name'=>$name,'status'=>$status,'category_id'=>$categoryId,'category_name'=>$category,'department_id'=>$departmentId,'department_name'=>$department,'purchase_date'=>$purchase,'purchase_price'=>$price,'lifespan_years'=>$life,'age_days'=>$age===null?null:(int)round($age*365.25),'age_years'=>$age,'age_ratio'=>$score['age_ratio'],'repair_count'=>$repairs,'repair_cost'=>$cost,'age_part'=>$score['parts']['age'],'repairs_part'=>$score['parts']['repairs'],'cost_part'=>$score['parts']['cost'],'score_raw'=>$score['score_raw']===null?null:sprintf('%.16F',$score['score_raw']),'tier'=>$score['tier']],$config['values']);
}
usort($rows,fn($a,$b)=>($a['score_raw']===null)<=>($b['score_raw']===null) ?: (float)$b['score_raw']<=>(float)$a['score_raw'] ?: $a['id']<=>$b['id']);
$data=['mock'=>true,'rows'=>$rows,'page'=>1,'per_page'=>20,'total'=>count($rows),'window'=>ItaLifecycleRules::window($today),'config'=>$config,'departments'=>[['value'=>'1','label'=>'Công nghệ thông tin'],['value'=>'2','label'=>'Vận hành']],'categories'=>array_map(fn($id,$name)=>['id'=>$id,'name'=>$name],[1,2,3,4,5],['Máy tính','Thiết bị lưu trữ','Màn hình','Máy in','Switch / Router'])];
file_put_contents(dirname(__DIR__).'/assets/ita/mock/lifecycle.json',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));echo "Synthetic lifecycle fixture generated, no DB.\n";
