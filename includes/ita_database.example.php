<?php
/** Copy to ita_database.local.php privately. Never commit or share its credentials. */
return [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'dung_infracore',
    'user' => '',
    'password' => '',
    'store_id' => 1,
    'auth_key' => '', // Generate at least 32 random bytes, e.g. bin2hex(random_bytes(32)).
    'allow_local_http' => false,
];
