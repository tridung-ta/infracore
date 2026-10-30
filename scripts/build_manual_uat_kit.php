<?php
/** Synthetic training files only. No bootstrap/config/DB/host access. */
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
define('ROOT_PATH', dirname(__DIR__).DIRECTORY_SEPARATOR);
require_once ROOT_PATH.'classes/security/itahttperror.class.php';
require_once ROOT_PATH.'classes/security/itainput.class.php';
require_once ROOT_PATH.'classes/services/itaexcel.class.php';
error_reporting(E_ALL);
$deprecations=0;
set_error_handler(static function(int $severity) use (&$deprecations): bool {
    if (in_array($severity,[E_DEPRECATED,E_USER_DEPRECATED],true)) { $deprecations++; }
    return false;
});
$options=getopt('',['output:']);$relative=$options['output']??'artifacts/ita-manual-uat-20261026';
if (!preg_match('/\Aartifacts\/ita-manual-uat-[a-zA-Z0-9-]+\z/D',$relative)) { throw new RuntimeException('Use a new private artifact directory.'); }
$target=ROOT_PATH.$relative;
if (file_exists($target) || is_link($target)) { throw new RuntimeException('Use a new output; never overwrite a filled test record.'); }
$guide=file_get_contents(ROOT_PATH.'docs/MANUAL_UAT_CLICK_GUIDE.md');
if ($guide===false) { throw new RuntimeException('Guide missing.'); }
preg_match_all('/^### (TC\d{2}) — (.+)$/mu',$guide,$cases,PREG_SET_ORDER);
if (count($cases)!==36) { throw new RuntimeException('Expected36 manual cases.'); }
mkdir($target,0700,true);
file_put_contents($target.'/HUONG_DAN_TUNG_BUOC.md',$guide);
$base=array_fill_keys(ItaExcel::HEADERS,'');
$base=array_replace($base,['category_code'=>'TEST26-CAT','department_code'=>'DIEN_MA_BO_PHAN_A','purchase_date'=>'2026-10-26','status'=>'in_use','purchase_price'=>'5000000','warranty_end'=>'2027-10-26','brand'=>'TEST','model'=>'UAT26','vendor'=>'TEST - không mua thật','location'=>'KHU TEST']);
$valid=[];
foreach ([1,2] as $i) { $valid[]=array_replace($base,['asset_code'=>'TEST26-IM0'.$i,'name'=>'TEST26 - Import '.$i,'serial_no'=>'TEST26-IMS0'.$i,'specs'=>$i===2?'=1+1':'Dữ liệu giả']); }
$invalid=[array_replace($base,['asset_code'=>'TEST26-MIX','name'=>'TEST26 - Dòng hợp lệ trong lô lỗi']),
    array_replace($base,['asset_code'=>'TEST26-01','name'=>'TEST26 - Trùng mã đã tạo tay']),
    array_replace($base,['asset_code'=>'TEST26-BADNEG','name'=>'TEST26 - Giá âm','purchase_price'=>'-1']),
    array_replace($base,['asset_code'=>'TEST26-BADSTATUS','name'=>'TEST26 - Sai trạng thái','status'=>'khong_hop_le']),
    array_replace($base,['asset_code'=>'TEST26-BADCAT','name'=>'TEST26 - Sai mã loại','category_code'=>'TEST26-NO-CAT'])];
$checks=0;
foreach (['01_IMPORT_2_DONG.xlsx'=>$valid,'02_IMPORT_CO_LOI.xlsx'=>$invalid] as $name=>$rows) {
    $bytes=ItaExcel::write(ItaExcel::HEADERS,$rows);file_put_contents($target.'/'.$name,$bytes);
    $read=ItaExcel::read($target.'/'.$name,$name);
    if (count($read)!==count($rows)) { throw new RuntimeException('Row count mismatch.'); }
    foreach ($rows as $i=>$row) { if ($read[$i]['values']!==$row) { throw new RuntimeException('Cell mismatch.'); } $checks++; }
}
file_put_contents($target.'/03_KHONG_PHAI_XLSX.xlsx',"TEST26 plain text only. Not PHP/EXE or a ZIP workbook.\n");
try { ItaExcel::read($target.'/03_KHONG_PHAI_XLSX.xlsx','03_KHONG_PHAI_XLSX.xlsx'); throw new RuntimeException('Invalid fixture accepted.'); }
catch (ItaHttpError $error) { if ($error->status!==422) { throw $error; } $checks++; }
$csv=fopen($target.'/KET_QUA.csv','x');fwrite($csv,"\xEF\xBB\xBF");
fputcsv($csv,['Mã','Tình huống','Kết quả (Đạt/Không/Chưa kiểm)','Ngày giờ VN','Vai trò test','Mã/ID giả','Bằng chứng đã che','Ghi chú/giới hạn'],',','"','');
foreach ($cases as $case) { fputcsv($csv,[$case[1],$case[2],'Chưa kiểm','','','','',''],',','"',''); }
fclose($csv);
$csv=fopen($target.'/SO_ID_TEST.csv','x');fwrite($csv,"\xEF\xBB\xBF");
fputcsv($csv,['Ký hiệu','Mã giả','ID số thực (tự ghi)','Bộ phận A/B (tên và mã tự ghi)','Ghi chú'],',','"','');
for($i=1;$i<=6;$i++) { fputcsv($csv,['M'.$i,'TEST26-0'.$i,'',$i<=3?'A':'B',''],',','"',''); }
foreach (['A','B','MA_A','C','AdminTest','ManagerTest','SupportTest','P1','P2','P3','P4','License1','License2','Plan2099'] as $key) { fputcsv($csv,[$key,'','','','Không ghi password/cookie/key'],',','"',''); }
fclose($csv);
file_put_contents($target.'/README.txt',"BỘ KIỂM THỦ CÔNG - KHÔNG PHẢI GÓI UPLOAD WEBSITE\n\n1. Mở HUONG_DAN_TUNG_BUOC.md, đọc phần0 trước.\n2. Đi theo menu từ Tổng quan đến Hạ tầng mạng;36 tình huống TC01-TC36.\n3. Ghi kết quả vào KET_QUA.csv, ID/mã riêng vào SO_ID_TEST.csv. Không ghi password/key/cookie.\n4. Hai XLSX cần thay DIEN_MA_BO_PHAN_A ở cộtF bằng mã bộ phận thật được phép và tạo loại TEST26-CAT nhưTC02. Chưa sửa placeholder thì KHÔNG được coi là file hợp lệ.\n5. 01_IMPORT_2_DONG có2dòng mẫu; 02_IMPORT_CO_LOI có5dòng, gồm1dòng tốt+4lỗi chủ ý, khôngconfirm lô này. 03_KHONG_PHAI_XLSX chỉ là văn bản trơ, không phải PHP/EXE.\n6. XLSX chỉ chọn qua nút Chọn file của ứng dụng; tuyệt đối không copy bộ này vào public_html/FTP.\n7. Fixture là dữ liệu giả. Chưa chạy nhập/DB/host; kết quả gửi lại nhãn: kiểm thủ công trên host, phạm vi hạn chế.\n8. Không SQL ghi/seed/reset/DDL/thu grant hoặc tải5000 dòng từ bộ này; thiếu điều kiện thì Chưa kiểm.\n");
echo json_encode(['output'=>$relative,'cases'=>count($cases),'xlsx_rows'=>[2,5],'roundtrip_and_rejection_checks'=>$checks,'deprecations'=>$deprecations,'db_connected'=>false],JSON_UNESCAPED_UNICODE).PHP_EOL;
