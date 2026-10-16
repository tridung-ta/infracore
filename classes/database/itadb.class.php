<?php
require_once __DIR__ . '/pmdb.class.php';

class ItaDb extends PmDb
{
    public function __construct(private object $legacyDatabase)
    {
        parent::__construct($legacyDatabase);
    }

    public function legacyDatabase(): object
    {
        return $this->legacyDatabase;
    }

    public static function configuration(): array
    {
        $file = dirname(__DIR__, 2) . '/includes/ita_database.local.php';
        $config = is_file($file) ? require $file : [];
        if (!is_array($config)) {
            throw new RuntimeException('Invalid private ITA configuration');
        }
        foreach (['host','port','name','user','password','store_id','auth_key','allow_local_http'] as $key) {
            $env = getenv('ITA_' . (in_array($key, ['auth_key','store_id','allow_local_http'], true) ? '' : 'DB_') . strtoupper($key));
            if ($env !== false) {
                $config[$key] = $env;
            }
        }
        $config += ['host'=>'localhost','port'=>3306,'name'=>'dung_infracore','user'=>'','password'=>'','store_id'=>1,'auth_key'=>'','allow_local_http'=>false];
        $config['ready'] = $config['user'] !== '' && strlen((string) $config['auth_key']) >= 32;
        return $config;
    }

    public static function connect(array $config): self
    {
        if (!$config['ready'] || (int) $config['store_id'] <= 0) {
            throw new RuntimeException('ITA database is not configured');
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = mysqli_init();
        $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $connection->real_connect($config['host'], $config['user'], $config['password'], $config['name'], (int) $config['port']);
        $connection->set_charset('utf8mb4');
        $connection->query("SET SESSION time_zone = '+00:00'");
        $connection->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
        return new self((object) ['connection'=>$connection]);
    }
}
