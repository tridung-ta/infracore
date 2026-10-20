<?php
class ItaLicenseCrypto
{
    private string $key;

    public function __construct(string $encodedKey)
    {
        $key = base64_decode($encodedKey,true);
        if (!function_exists('openssl_encrypt') || !in_array('aes-256-gcm',openssl_get_cipher_methods(),true) || $key===false || strlen($key)!==32) {
            throw new ItaHttpError(503,'Chưa cấu hình khóa mã hóa giấy phép hợp lệ.');
        }
        $this->key = $key;
    }

    public static function configured(): self
    {
        return new self((string) (getenv('ITA_LICENSE_KEY') ?: ''));
    }

    public function encrypt(string $plain, string $context): string
    {
        $nonce = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain,'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,$nonce,$tag,$context,16);
        if ($cipher===false) {
            throw new ItaHttpError(503,'Không thể mã hóa giấy phép.');
        }
        return 'v1:'.base64_encode($nonce.$tag.$cipher);
    }

    public function decrypt(string $encoded, string $context): string
    {
        $bytes = str_starts_with($encoded,'v1:') ? base64_decode(substr($encoded,3),true) : false;
        if ($bytes===false || strlen($bytes)<28) {
            throw new ItaHttpError(503,'Không thể đọc khóa giấy phép.');
        }
        $plain = openssl_decrypt(substr($bytes,28),'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,substr($bytes,0,12),substr($bytes,12,16),$context);
        if ($plain===false) {
            throw new ItaHttpError(503,'Không thể đọc khóa giấy phép.');
        }
        return $plain;
    }
}
