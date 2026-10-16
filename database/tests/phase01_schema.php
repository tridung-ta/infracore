<?php
/** Local-only schema integration checks. Never points at hosting. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('127.0.0.1', 'root', '', 'dung_infracore', 18767);
$db->set_charset('utf8mb4');
$db->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
$db->query("SET SESSION time_zone = '+00:00'");
$total = 0;

function accepts(mysqli $db, string $sql): void
{
    global $total;
    $db->query($sql);
    $total++;
}

function rejects(mysqli $db, string $sql, int $expectedCode): void
{
    global $total;
    try {
        $db->query($sql);
    } catch (mysqli_sql_exception $error) {
        if ($error->getCode() !== $expectedCode) {
            throw new RuntimeException('Unexpected database error code: ' . $error->getCode());
        }
        $total++;
        return;
    }
    throw new RuntimeException('Expected constraint rejection did not occur');
}

$tables = $db->query("SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'dung_infracore'")->fetch_assoc();
if ((int) $tables['n'] !== 12) {
    throw new RuntimeException('Expected isolated 12-table ITA fixture');
}
$db->begin_transaction();
try {
    accepts($db, "INSERT INTO ita_departments (id,store_id,code,name) VALUES (1,1,'IT','IT QA'),(2,2,'IT','Other tenant')");
    accepts($db, "INSERT INTO ita_asset_categories (id,store_id,code,name) VALUES (1,1,'PC','PC QA'),(2,2,'PC','Other tenant')");
    accepts($db, "INSERT INTO ita_assets (id,store_id,asset_code,category_id,name,department_id,created_by) VALUES (1,1,'QA-001',1,'QA device',1,1),(2,2,'QA-001',2,'Other device',2,1)");
    rejects($db, "INSERT INTO ita_assets (store_id,asset_code,category_id,name,created_by) VALUES (1,'qa-001',1,'Duplicate',1)", 1062);
    rejects($db, "INSERT INTO ita_assets (store_id,asset_code,category_id,name,created_by) VALUES (1,'BAD-CATEGORY',2,'Cross tenant',1)", 1452);
    rejects($db, "INSERT INTO ita_assets (store_id,asset_code,category_id,name,department_id,created_by) VALUES (1,'BAD-DEPT',1,'Cross tenant',2,1)", 1452);
    rejects($db, "INSERT INTO ita_assets (store_id,asset_code,category_id,name,purchase_price,created_by) VALUES (1,'BAD-PRICE',1,'Negative',-1,1)", 3819);
    rejects($db, "INSERT INTO ita_assets (store_id,asset_code,category_id,name,status,created_by) VALUES (1,'BAD-RETIRED',1,'Retired','retired',1)", 3819);
    accepts($db, "INSERT INTO ita_assignments (store_id,asset_id,to_user_id,assigned_by,handover_no) VALUES (1,1,1,1,'QA-HO-1')");
    rejects($db, "INSERT INTO ita_assignments (store_id,asset_id,to_user_id,assigned_by,handover_no) VALUES (1,1,2,1,'QA-HO-2')", 1062);
    rejects($db, "INSERT INTO ita_assignments (store_id,asset_id,to_user_id,assigned_by,handover_no) VALUES (1,2,1,1,'QA-HO-CROSS')", 1452);
    rejects($db, "INSERT INTO ita_assignments (store_id,asset_id,to_user_id,to_department_id,assigned_by,handover_no) VALUES (1,1,1,1,1,'QA-HO-BOTH')", 3819);
    accepts($db, "UPDATE ita_assignments SET returned_at=assigned_at WHERE store_id=1 AND asset_id=1");
    accepts($db, "INSERT INTO ita_assignments (store_id,asset_id,to_user_id,assigned_by,handover_no) VALUES (1,1,2,1,'QA-HO-2')");
    accepts($db, "INSERT INTO ita_licenses (id,store_id,software_name,license_type,total_seats,created_by) VALUES (1,1,'QA License','per_device',1,1)");
    accepts($db, "INSERT INTO ita_license_allocations (store_id,license_id,asset_id,allocated_by) VALUES (1,1,1,1)");
    rejects($db, "INSERT INTO ita_license_allocations (store_id,license_id,asset_id,allocated_by) VALUES (1,1,1,1)", 1062);
    rejects($db, "INSERT INTO ita_license_allocations (store_id,license_id,asset_id,allocated_by) VALUES (1,1,2,1)", 1452);
    rejects($db, "INSERT INTO ita_license_allocations (store_id,license_id,allocated_by) VALUES (1,1,1)", 3819);
    accepts($db, "UPDATE ita_license_allocations SET released_at=allocated_at WHERE store_id=1 AND license_id=1");
    accepts($db, "INSERT INTO ita_license_allocations (store_id,license_id,asset_id,allocated_by) VALUES (1,1,1,1)");
    accepts($db, "INSERT INTO ita_tickets (id,store_id,ticket_no,asset_id,reported_by,issue) VALUES (1,1,'QA-TICKET',1,1,'QA issue')");
    accepts($db, "INSERT INTO ita_ticket_events (store_id,ticket_id,from_status,to_status,user_id) VALUES (1,1,NULL,'pending',1),(1,1,'pending','failed',1),(1,1,'failed','pending',1)");
    rejects($db, "INSERT INTO ita_ticket_events (store_id,ticket_id,from_status,to_status,user_id) VALUES (1,1,NULL,'resolved',1)", 3819);
    rejects($db, "INSERT INTO ita_ticket_events (store_id,ticket_id,from_status,to_status,user_id) VALUES (1,1,'resolved','pending',1)", 3819);
    rejects($db, "INSERT INTO ita_audit_logs (store_id,user_id,action,entity_type,entity_id,after_json) VALUES (1,1,'create','asset',1,'invalid-json')", 3819);
    accepts($db, "INSERT INTO ita_audit_logs (store_id,user_id,action,entity_type,entity_id,after_json) VALUES (1,1,'create','asset',1,'{}')");
    rejects($db, "INSERT INTO ita_budget_plans (store_id,year,department_id,status,created_by) VALUES (1,2026,1,'finalized',1)", 3819);
    accepts($db, "INSERT INTO ita_budget_plans (store_id,year,department_id,created_by) VALUES (1,2026,1,1)");
    rejects($db, "INSERT INTO ita_import_batches (store_id,user_id,filename,total_rows,ok_rows,error_rows) VALUES (1,1,'qa.xlsx',1,1,1)", 3819);
    echo json_encode(['checks' => $total, 'result' => 'PASS', 'server' => $db->server_info, 'scope' => 'isolated local MySQL; hosting MariaDB not executed'], JSON_UNESCAPED_SLASHES) . PHP_EOL;
} finally {
    $db->rollback();
    $db->close();
}
