<?php
/** Synthetic preview only; scripted DB object, no private config/connection. */
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
require dirname(__DIR__).'/tests/fixtures/ita_forecast_db.php';
$db=new ForecastTestDb();
$actor=new ItaUserObjectInfo(['id'=>1,'store_id'=>1,'role'=>'admin_it','auth_revision'=>1,'fullname'=>'Mock','username'=>'mock']);
$dao=new ItaForecastObjects($db,$actor);$service=new ItaForecastService($db,$actor);
$service->configure(['category_id'=>1,'version'=>$dao->configuration()['version'],'price'=>'20000000.00','units_per_person'=>'1.5','maintenance_percent'=>'10']);
$service->save(['year'=>date('Y'),'department_id'=>10,'planned_headcount_increase'=>3,'note'=>'Dữ liệu giả, không dùng cho kế hoạch thật']);
$data=$dao->calculate(['year'=>date('Y')]);$data['mock']=true;
file_put_contents(dirname(__DIR__).'/assets/ita/mock/forecast.json',json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n");
echo "Generated synthetic forecast fixture; no DB.\n";
