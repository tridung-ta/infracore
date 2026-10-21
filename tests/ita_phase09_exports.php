<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/includes/ita_bootstrap.inc.php';
$document=json_decode(file_get_contents(dirname(__DIR__).'/assets/ita/mock/forecast.json'),true,32,JSON_THROW_ON_ERROR);
$document['rows'][0]['department_name']='=HYPERLINK("https://example.invalid","x")';
$document['rows'][0]['category_name']='Thiết bị kiểm thử tiếng Việt';
$bytes=ItaForecastExport::render($document,'xlsx');
$path=tempnam(sys_get_temp_dir(),'ita-budget-');file_put_contents($path,$bytes);
try {
 $zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('Invalid XLSX');
 $sheet=$zip->getFromName('xl/worksheets/sheet1.xml');$zip->close();
 if(str_contains($sheet,'<f>')||!str_contains($sheet,'t="s"'))throw new RuntimeException('Excel formula protection');
 $reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();$book=$reader->load($path);$found=false;
 foreach($book->getActiveSheet()->getRowIterator() as $row){foreach($row->getCellIterator() as $cell){if(str_starts_with((string)$cell->getValue(),'=HYPERLINK')){if($cell->getDataType()!=='s')throw new RuntimeException('Formula typed');$found=true;}}}
 $book->disconnectWorksheets();if(!$found)throw new RuntimeException('Text not preserved');
} finally { unlink($path); }
$directory=dirname(__DIR__).'/tmp/phase09-verify';if(!is_dir($directory))mkdir($directory,0700,true);
file_put_contents($directory.'/budget.xlsx',$bytes);
$pdf=ItaForecastExport::render($document,'pdf');if(!str_starts_with($pdf,'%PDF'))throw new RuntimeException('Invalid PDF');
file_put_contents($directory.'/budget.pdf',$pdf);
echo "Real XLSX reopened: formula-like text stays STRING; real Vietnamese PDF rendered, synthetic data only.\n";
