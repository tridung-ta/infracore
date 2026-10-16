<?php
/** Isolated local fixture only. Does not read hosting credentials or data. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root = dirname(__DIR__);
$db = new mysqli('127.0.0.1','root','','',18767);
$db->set_charset('utf8mb4');
$schema = 'infracore_auth_qa_' . bin2hex(random_bytes(5));
$db->query('CREATE DATABASE `' . $schema . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$bundle = str_replace('dung_infracore',$schema,file_get_contents($root.'/database/host/phase01_create_ita_tables.sql'));
$migration = str_replace('dung_infracore',$schema,file_get_contents($root.'/database/migrations/005_ita_auth.sql'));
$source = file_get_contents($root.'/dung_infracore.sql');
if (!preg_match('/^CREATE TABLE `dc_users` \([\s\S]*?^\) [^;]+;/m',$source,$table)) {
    throw new RuntimeException('Missing reference user schema');
}
preg_match_all('/^ALTER TABLE `dc_users`\s+[\s\S]*?;/m',$source,$alter);
$sql = $bundle . "\n" . $table[0] . "\n" . implode("\n",$alter[0]) . "\n" . $migration;
$db->multi_query($sql);
do {
    if ($result=$db->store_result()) {
        $result->free();
    }
} while ($db->more_results() && $db->next_result());
$hash = password_hash('Local-QA-only-2026!',PASSWORD_DEFAULT);
$insert=$db->prepare('INSERT INTO dc_users (id,store_id,username,password,password_hash,fullname,email,status,type) VALUES (?,?,?,?,?,?,?,?,1)');
foreach ([9001=>'admin',9002=>'manager',9003=>'support',9004=>'unassigned',9005=>'inactive',9007=>'otherstore'] as $id=>$name) {
    $store=$id===9007?2:1;
    $legacy=$id===9003?md5('Local-QA-only-2026!'):null;
    $modern=$id===9003?null:$hash;
    $fullname='QA '.$name;
    $email=$name.'@example.invalid';
    $status=$id===9005?0:1;
    $insert->bind_param('iisssssi',$id,$store,$name,$legacy,$modern,$fullname,$email,$status);
    $insert->execute();
}
$db->query("INSERT INTO ita_departments (id,store_id,code,name) VALUES (1001,1,'D1','QA Department One'),(1002,1,'D2','QA Department Two'),(2001,2,'D1','Other tenant')");
$db->query("INSERT INTO ita_asset_categories (id,store_id,code,name) VALUES (1001,1,'PC','Máy tính'),(2001,2,'PC','Máy tính')");
$db->query("INSERT INTO ita_user_roles (store_id,user_id,role) VALUES (1,9001,'admin_it'),(1,9002,'system_manager'),(1,9003,'support_technician'),(1,9005,'admin_it'),(2,9007,'admin_it')");
$db->query('INSERT INTO ita_user_scopes (store_id,user_id,department_id) VALUES (1,9002,1001),(1,9003,1002)');
$insert=$db->prepare('INSERT INTO ita_assets (id,store_id,asset_code,category_id,name,department_id,status,created_by,specs) VALUES (?,1,?,1001,?,?,?,9001,?)');
for ($i=1;$i<=12;$i++) {
    $id=1000+$i;
    $code='QA-'.str_pad((string)$i,3,'0',STR_PAD_LEFT);
    $name='QA Device '.$i;
    $department=$i%2?1001:1002;
    $status=$i%4===0?'broken':($i%3===0?'maintenance':'in_use');
    $specs='16 GB RAM · QA fixture';
    $insert->bind_param('ississ',$id,$code,$name,$department,$status,$specs);
    $insert->execute();
}
$db->query("INSERT INTO ita_assets (id,store_id,asset_code,category_id,name,department_id,created_by) VALUES (1099,1,'UNASSIGNED',1001,'QA Inventory',NULL,9001),(2001,2,'OTHER',2001,'Other tenant device',2001,9007)");
file_put_contents($root.'/tests/ita_phase02_context.tmp',json_encode(['schema'=>$schema]));
echo json_encode(['schema'=>$schema,'tables'=>'12 ITA + 3 auth + dc_users MyISAM','users'=>'synthetic local only']) . PHP_EOL;
