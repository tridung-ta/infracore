<?php
/** Parse host preflight/bootstrap/verification on the dedicated local fixture, never hosting. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$schema = json_decode(file_get_contents(__DIR__.'/ita_phase02_context.tmp'),true)['schema'];
if (!preg_match('/^infracore_auth_qa_[a-f0-9]{10}$/D',$schema)) {
    throw new RuntimeException('Invalid local fixture');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connection = new mysqli('127.0.0.1','root','',$schema,18767);
foreach (['phase02_preflight','phase02_bootstrap_admin','phase02_verify'] as $file) {
    $sql = file_get_contents(dirname(__DIR__).'/database/host/'.$file.'.sql');
    // Fixture's first admin is 9001, not hosting account 111.
    $sql = str_replace(['dung_infracore','111'],[$schema,'9001'],$sql);
    $connection->multi_query($sql);
    do {
        if ($result = $connection->store_result()) {
            $result->free();
        }
    } while ($connection->more_results() && $connection->next_result());
}
$row = $connection->query('SELECT role,enabled FROM ita_user_roles WHERE store_id=1 AND user_id=9001')->fetch_assoc();
if ($row['role'] !== 'admin_it' || (int) $row['enabled'] !== 1) {
    throw new RuntimeException('Bootstrap changed existing administrator');
}
echo "3 SQL scripts executed locally; existing admin preserved.\n";
foreach (['SELECT id FROM ita_assets WHERE store_id=1 AND is_deleted=0 AND department_id=1002 ORDER BY id DESC LIMIT 5',
    'SELECT status,COUNT(*) FROM ita_assets WHERE store_id=1 AND is_deleted=0 AND department_id=1002 GROUP BY status'] as $sql) {
    $plan = $connection->query('EXPLAIN '.$sql)->fetch_assoc();
    echo json_encode(['query'=>'scoped assets','access'=>$plan['type'],'key'=>$plan['key'],'rows'=>$plan['rows']]).PHP_EOL;
}
