<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/ita_phase10_unit.php';
$db=new DashboardTestDb();$db->assets=[$a];$db->assets[0]['name']='=HYPERLINK("https://invalid.example")';
$service=new ItaReportService($db,$admin,str_repeat('x',32));$preview=$service->preview(['report'=>'inventory'],1);
$input=['report'=>'inventory','as_of'=>$preview['as_of'],'token'=>$preview['token'],'format'=>'xlsx'];
$file=$service->export($input);if(!str_starts_with($file['bytes'],'PK'))throw new RuntimeException('XLSX signature');
$directory=dirname(__DIR__).'/tmp/phase10-verify';if(!is_dir($directory))mkdir($directory,0700,true);
file_put_contents($directory.'/inventory.xlsx',$file['bytes']);
$reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();$book=$reader->load($directory.'/inventory.xlsx');$found=false;
foreach($book->getActiveSheet()->getRowIterator() as $row){foreach($row->getCellIterator() as $cell){if(str_starts_with((string)$cell->getValue(),'=HYPERLINK')){if($cell->getDataType()!=='s')throw new RuntimeException('Formula injection');$found=true;}}}
$book->disconnectWorksheets();if(!$found)throw new RuntimeException('Missing text');
echo "Real inventory XLSX reopened as STRING; no DB.\n";
