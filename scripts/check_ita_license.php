<?php
// CLI-only, no configuration/DB bootstrap and no secret output.
if (PHP_SAPI!=='cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__.'/../classes/security/itahttperror.class.php';
require_once __DIR__.'/../classes/services/italicensecrypto.class.php';
try {
    $crypto = ItaLicenseCrypto::configured();
    $cipher = $crypto->encrypt('preflight','ita-preflight');
    if ($crypto->decrypt($cipher,'ita-preflight')!=='preflight') {
        throw new RuntimeException();
    }
    echo "OK: AES-256-GCM, 32-byte key and authenticated round trip. No DB accessed.\n";
} catch (Throwable $error) {
    fwrite(STDERR,"FAIL: configure OpenSSL AES-256-GCM and ITA_LICENSE_KEY (base64 of 32 random bytes).\n");
    exit(1);
}
