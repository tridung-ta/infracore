<?php
/** Dedicated disposable DB only. No inherited ITA environment/configuration. */
final class LocalFixture
{
    public const TABLES = ['ita_departments','ita_asset_categories','ita_settings','ita_assets','ita_licenses','ita_license_allocations','ita_assignments','ita_tickets','ita_ticket_events','ita_audit_logs','ita_budget_plans','ita_import_batches','ita_user_roles','ita_user_scopes','ita_login_limits','ita_network_links'];

    public function __construct(private mysqli $db, private array $config) {}

    public static function authorize(array $options): void
    {
        $name = $options['db'] ?? '';
        if (!is_string($name) || !preg_match('/\Ainfracore_local_[a-z0-9_]{1,40}\z/D', $name)
            || !array_key_exists('execute', $options)
            || ($options['ack'] ?? '') !== 'Tôi xác nhận cho chạy trên DB local ' . $name
            || !in_array($options['action'] ?? '', ['seed','load'], true)) {
            throw new LogicException('Explicit local authorization required.');
        }
    }

    public static function configuration(string $path, string $name): array
    {
        $resolved = realpath($path);
        $root = realpath(dirname(__DIR__, 2));
        if (!$resolved || strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) !== 'json'
            || str_starts_with(strtolower(str_replace('\\','/', $resolved)), strtolower(str_replace('\\','/', $root)) . '/')) {
            throw new LogicException('Private local JSON must be outside workspace/public root.');
        }
        $c = json_decode(file_get_contents($resolved), true, 32, JSON_THROW_ON_ERROR);
        return self::validateConfiguration($c, $name);
    }

    public static function validateConfiguration(array $c, string $name): array
    {
        if (!preg_match('/\Ainfracore_local_[a-z0-9_]{1,40}\z/D', $name)
            || ($c['purpose'] ?? '') !== 'infracore-local-fixture-v1'
            || ($c['local_only'] ?? false) !== true || ($c['no_tunnel'] ?? false) !== true
            || ($c['connection_role'] ?? '') !== 'setup' || ($c['name'] ?? '') !== $name
            || !in_array($c['host'] ?? '', ['localhost','127.0.0.1','::1'], true)
            || !is_int($c['port'] ?? null) || $c['port'] < 1 || $c['port'] > 65535
            || !is_string($c['user'] ?? null) || $c['user'] === ''
            || !is_string($c['password'] ?? null)
            || !is_string($c['test_password'] ?? null) || strlen($c['test_password']) < 12 || strlen($c['test_password']) > 64
            || strlen(base64_decode($c['test_license_key'] ?? '', true) ?: '') !== 32) {
            throw new LogicException('Invalid dedicated local configuration.');
        }
        $c['host'] = $c['host'] === 'localhost' ? '127.0.0.1' : $c['host'];
        return $c;
    }

    public function run(string $action): void
    {
        if ($this->scalar('SELECT DATABASE()') !== $this->config['name']) { throw new LogicException('Wrong schema.'); }
        $expected = [...self::TABLES, 'dc_users'];
        $actual = $this->db->query('SHOW FULL TABLES')->fetch_all(MYSQLI_NUM);
        if (count($actual) !== count($expected)) { throw new LogicException('Dedicated 17-table schema required.'); }
        foreach ($actual as $table) {
            if (!in_array($table[0], $expected, true) || $table[1] !== 'BASE TABLE') { throw new LogicException('Unexpected table/view.'); }
        }
        if ($this->db->query('SHOW TRIGGERS')->num_rows !== 0) { throw new LogicException('Triggers refused.'); }
        // A shared DB, view or nontransactional ITA table is never a seed target.
        foreach (self::TABLES as $table) {
            $s = $this->db->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
            $s->execute([$table]);
            if (($s->get_result()->fetch_assoc()['ENGINE'] ?? '') !== 'InnoDB') { throw new LogicException('Missing/non-InnoDB table.'); }
        }
        if ($action === 'seed') { $this->seed(); }
        elseif ($action === 'load') { $this->load(); }
        else { throw new LogicException('Unknown action.'); }
    }

    private function scalar(string $sql): mixed
    {
        return $this->db->query($sql)->fetch_row()[0];
    }

    private function insert(string $table, array $values): void
    {
        if (!in_array($table, [...self::TABLES, 'dc_users'], true)) { throw new LogicException('Unknown table.'); }
        foreach (array_keys($values) as $column) {
            if (!preg_match('/\A[a-z_]+\z/D', $column)) { throw new LogicException('Unknown column.'); }
        }
        $columns = '`' . implode('`,`', array_keys($values)) . '`';
        $sql = 'INSERT INTO `' . $table . '` (' . $columns . ') VALUES (' . implode(',', array_fill(0, count($values), '?')) . ')';
        $this->db->prepare($sql)->execute(array_values($values));
    }

    private function nativeDefaults(): array
    {
        $required = ['id','store_id','username','email','fullname','status','password','password_hash','last_login'];
        $columns = $this->db->query('SHOW COLUMNS FROM dc_users')->fetch_all(MYSQLI_ASSOC);
        $names = array_column($columns, 'Field');
        if (array_diff($required, $names)) { throw new LogicException('Missing legacy columns.'); }
        $defaults = $this->config['legacy_defaults'] ?? [];
        if (!is_array($defaults) || array_intersect(array_keys($defaults), $required) || array_diff(array_keys($defaults), $names)) {
            throw new LogicException('Invalid legacy defaults.');
        }
        foreach ($columns as $column) {
            if (!in_array($column['Field'], $required, true) && $column['Null'] === 'NO' && $column['Default'] === null
                && !str_contains($column['Extra'], 'auto_increment') && !str_contains($column['Extra'], 'GENERATED')
                && !array_key_exists($column['Field'], $defaults)) {
                throw new LogicException('Extra required legacy column: ' . $column['Field']);
            }
        }
        return $defaults;
    }

    private function seed(): void
    {
        foreach ([...self::TABLES, 'dc_users'] as $table) {
            if ((int) $this->scalar('SELECT COUNT(*) FROM `' . $table . '`') !== 0) { throw new LogicException('Seed requires empty tables.'); }
        }
        $defaults = $this->nativeDefaults();
        require_once dirname(__DIR__, 2) . '/classes/services/italicensecrypto.class.php';
        $crypto = new ItaLicenseCrypto($this->config['test_license_key']);
        $encrypted = $crypto->encrypt('TEST-LICENSE-NOT-REAL', 'ita-license-store:1');
        $password = $this->config['test_password'];
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // MyISAM rows cannot be rolled back. All emptiness/schema/key checks precede these writes.
        foreach (['admin','manager-a','manager-b','support'] as $i => $name) {
            $this->insert('dc_users', ['id'=>900001+$i,'store_id'=>1,'username'=>'test-'.$name,
                'email'=>'test-'.$name.'@example.invalid','fullname'=>'TEST '.$name,'status'=>1,
                'password'=>md5($password),'password_hash'=>$i === 1 ? '' : $hash,'last_login'=>'2000-01-01 00:00:00'] + $defaults);
        }
        $this->db->begin_transaction();
        try { $this->seedIta($encrypted); $this->db->commit(); }
        catch (Throwable $error) { $this->db->rollback(); throw $error; }
        echo "seed: native=4; departments=2; assets=6; tickets=7. Budget draft NOT seeded: save through UI.\n";
    }

    private function seedIta(string $encrypted): void
    {
        foreach ([1001=>'A',1002=>'B'] as $id=>$name) {
            $this->insert('ita_departments', ['id'=>$id,'store_id'=>1,'code'=>'TEST-'.$name,'name'=>'TEST Department '.$name]);
        }
        foreach (['admin_it','system_manager','system_manager','support_technician'] as $i=>$role) {
            $this->insert('ita_user_roles', ['store_id'=>1,'user_id'=>900001+$i,'role'=>$role]);
            if ($i) { $this->insert('ita_user_scopes', ['store_id'=>1,'user_id'=>900001+$i,'department_id'=>$i===2 ? 1002 : 1001]); }
        }
        foreach ([2001=>'COMPUTER',2002=>'NETWORK',2003=>'BUDGET',2004=>'SOFTWARE',2005=>'PDF-500'] as $id=>$name) {
            $this->insert('ita_asset_categories', ['id'=>$id,'store_id'=>1,'code'=>'TEST-'.$name,'name'=>'TEST '.$name,
                'is_software'=>$id===2004 ? 1 : 0,'default_lifespan_years'=>$id===2002 ? 8 : 5,
                'maintenance_cycle_months'=>12,'default_replacement_cost'=>20000000]);
        }
        $parameters = [];
        foreach ([2001,2002,2003,2005] as $id) { $parameters[$id] = ['units_per_person'=>$id===2003 ? '1.5' : '0','maintenance_percent'=>'0']; }
        $settings = ['budget_contingency_percent'=>'10','currency'=>'VND','display_timezone'=>'Asia/Ho_Chi_Minh',
            'license_critical_days'=>'7','license_warning_days'=>'30','warranty_warning_days'=>'30',
            'lifecycle_weight_age'=>'0.5','lifecycle_weight_repairs'=>'0.3','lifecycle_weight_cost'=>'0.2',
            'lifecycle_replace_threshold'=>'0.8','lifecycle_watch_threshold'=>'0.6',
            'forecast_category_parameters'=>json_encode($parameters, JSON_THROW_ON_ERROR)];
        foreach ($settings as $key=>$value) { $this->insert('ita_settings', ['store_id'=>1,'setting_key'=>$key,'setting_value'=>$value]); }
        for ($i=1; $i<=6; $i++) {
            $this->asset(3000+$i, $i===6 ? 2003 : (in_array($i,[3,5],true) ? 2002 : 2001), $i<=3 ? 1001 : 1002,
                $i===5 ? 'maintenance' : 'in_use', $i===6 ? '-10 years' : '-2 years');
        }
        foreach ([1,2,3] as $i) { $this->ticket(4000+$i,3002,gmdate('Y-m-d H:i:s',time()-$i*60*86400),1000000,'repair','resolved'); }
        $year = (int) (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y');
        foreach ([1,2,3] as $i) { $this->ticket(4010+$i,3006,($year-$i).'-07-15 03:00:00',3000000,$i===2?'periodic':'repair','resolved'); }
        $this->ticket(4020,3005,gmdate('Y-m-d H:i:s'),0,'periodic','pending');
        foreach ([7001=>'+30 days',7002=>'-1 day'] as $id=>$expiry) {
            $this->insert('ita_licenses', ['id'=>$id,'store_id'=>1,'software_name'=>'TEST License '.$id,
                'license_key_enc'=>$encrypted,'license_type'=>'per_device','total_seats'=>2,'expire_date'=>gmdate('Y-m-d',strtotime($expiry)), 'created_by'=>900001]);
        }
        $this->insert('ita_license_allocations', ['store_id'=>1,'license_id'=>7001,'asset_id'=>3001,'allocated_by'=>900001]);
        foreach ([[3001,3002],[3001,3004]] as [$from,$to]) {
            $this->insert('ita_network_links', ['store_id'=>1,'source_asset_id'=>$from,'target_asset_id'=>$to,'link_type'=>'ethernet','note'=>'TEST fixture']);
        }
        $this->insert('ita_settings', ['store_id'=>1,'setting_key'=>'_local_fixture_marker','setting_value'=>$this->config['name']]);
    }

    private function asset(int $id, int $category, int $department, string $status='in_use', string $age='-2 years'): void
    {
        $this->insert('ita_assets', ['id'=>$id,'store_id'=>1,'asset_code'=>'TEST-'.$id,'name'=>'TEST Asset '.$id,
            'category_id'=>$category,'department_id'=>$department,'status'=>$status,
            'purchase_date'=>gmdate('Y-m-d',strtotime($age)),'purchase_price'=>20000000,'created_by'=>900001]);
    }

    private function ticket(int $id, int $asset, string $opened, int $cost, string $type, string $status): void
    {
        $closed = $status==='pending' ? null : gmdate('Y-m-d H:i:s',strtotime($opened.' UTC')+3600);
        $this->insert('ita_tickets', ['id'=>$id,'store_id'=>1,'ticket_no'=>'TEST-TICKET-'.$id,'asset_id'=>$asset,
            'type'=>$type,'reported_by'=>900001,'technician_id'=>$asset===3002 ? 900004 : 900001,'issue'=>'TEST fixture','status'=>$status,
            'opened_at'=>$opened,'resolved_at'=>$closed,'handled_date'=>$closed ? substr($closed,0,10) : null,'cost'=>$cost]);
        $this->insert('ita_ticket_events', ['store_id'=>1,'ticket_id'=>$id,'from_status'=>null,'to_status'=>'pending','user_id'=>900001,'created_at'=>$opened]);
        if ($closed) { $this->insert('ita_ticket_events', ['store_id'=>1,'ticket_id'=>$id,'from_status'=>'pending','to_status'=>$status,'user_id'=>$asset===3002 ? 900004 : 900001,'created_at'=>$closed]); }
    }

    private function load(): void
    {
        if ($this->scalar("SELECT setting_value FROM ita_settings WHERE store_id=1 AND setting_key='_local_fixture_marker'") !== $this->config['name']
            || (int)$this->scalar("SELECT COUNT(*) FROM dc_users WHERE store_id<>1 OR username NOT LIKE 'test-%'") !== 0
            || (int)$this->scalar("SELECT COUNT(*) FROM ita_assets WHERE store_id<>1 OR asset_code NOT LIKE 'TEST-%'") !== 0
            || (int)$this->scalar('SELECT COUNT(*) FROM ita_assets') !== 6
            || (int)$this->scalar('SELECT COUNT(*) FROM ita_assets WHERE category_id=2005') !== 0) {
            throw new LogicException('Load requires untouched six-asset fixture.');
        }
        $this->db->begin_transaction();
        try {
            for ($i=1; $i<=4994; $i++) {
                $this->asset(100000+$i, $i<=500 ? 2005 : 2001, $i<=500 || $i%2 ? 1001 : 1002);
                if ($i%10===0) { $this->ticket(200000+$i,100000+$i,gmdate('Y-m-d H:i:s',time()-30*86400),100000,'repair','resolved'); }
            }
            $this->db->commit();
        } catch (Throwable $error) { $this->db->rollback(); throw $error; }
        echo 'assets=' . $this->scalar('SELECT COUNT(*) FROM ita_assets') . "; PDF category2005=500 expected; verify actual count.\n";
    }
}
