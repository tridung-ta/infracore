<?php
/** CLI checks and repeatable reset of synthetic data on the isolated QA server only. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ROOT_PATH', dirname(__DIR__).'/');
require ROOT_PATH.'includes/ita_bootstrap.inc.php';
$context = json_decode(file_get_contents(__DIR__.'/ita_phase02_context.tmp'), true);
$schema = $context['schema'];
if (!preg_match('/^infracore_auth_qa_[a-f0-9]{10}$/D', $schema)) {
    throw new RuntimeException('Not a disposable QA schema');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connection = new mysqli('127.0.0.1','root','',$schema,18767);
$connection->set_charset('utf8mb4');
$db = new ItaDb((object) ['connection'=>$connection]);
$checks = 0;
$check = static function (bool $ok) use (&$checks): void {
    if (!$ok) {
        throw new RuntimeException('QA assertion failed at '.($checks+1));
    }
    $checks++;
};
if (!in_array('--reset-only', $argv, true)) {
$support = $db->fetchOne('SELECT password_hash FROM dc_users WHERE id=9003');
$check(password_verify('Local-QA-only-2026!', (string) $support['password_hash']));
$actor = (new ItaUserObjects($db))->current(1,9001);
$db->beginTransaction();
(new ItaAuditService($db,$actor))->record('asset.update','asset',1001,['before'=>['name'=>'Before','password'=>'secret'], 'after'=>['name'=>'After','license_key'=>'secret','session'=>'secret']]);
$last = $db->fetchOne('SELECT before_json,after_json FROM ita_audit_logs ORDER BY id DESC LIMIT 1');
$check(!str_contains(json_encode($last),'secret') && str_contains($last['after_json'],'After'));
$db->rollBack();
$check((new ItaUserObjects($db))->current(1,9007) === null);
$check((new ItaUserObjects($db))->current(1,9004) !== null);
$db->execute('UPDATE ita_user_roles SET enabled=0 WHERE store_id=1 AND user_id=9004');
$check((new ItaUserObjects($db))->current(1,9004) === null);
$longPassword = str_repeat('L', 80).'QA-suffix';
$db->execute('UPDATE dc_users SET password_hash=NULL,password=? WHERE id=9003','s',[md5($longPassword)]);
$auth = new ItaAuthService($db,['store_id'=>1,'auth_key'=>bin2hex(random_bytes(32))]);
$check($auth->authenticate('support',$longPassword,'127.0.0.1')->id === 9003);
$check(empty($db->fetchOne('SELECT password_hash FROM dc_users WHERE id=9003')['password_hash']));
try {
    $auth->authenticate('support',str_repeat('L',80).'wrong','127.0.0.1');
    $check(false);
} catch (ItaHttpError $error) {
    $check($error->status === 401);
}
}
// Only synthetic fixture IDs in this dedicated, explicitly named local schema.
$connection->query('DELETE FROM ita_user_scopes WHERE user_id BETWEEN 9001 AND 9007');
$connection->query('DELETE FROM ita_user_roles WHERE user_id BETWEEN 9001 AND 9007');
$connection->query("INSERT INTO ita_user_roles (store_id,user_id,role) VALUES (1,9001,'admin_it'),(1,9002,'system_manager'),(1,9003,'support_technician'),(1,9005,'admin_it'),(2,9007,'admin_it')");
$connection->query('INSERT INTO ita_user_scopes (store_id,user_id,department_id) VALUES (1,9002,1001),(1,9003,1002)');
$connection->query('DELETE FROM ita_login_limits');
$connection->query('DELETE FROM ita_audit_logs');
$db->execute('UPDATE dc_users SET password_hash=NULL,password=? WHERE id=9003', 's',[md5('Local-QA-only-2026!')]);
echo $checks." integration checks passed; synthetic QA fixture reset.\n";
