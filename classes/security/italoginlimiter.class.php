<?php
class ItaLoginLimiter
{
    public function __construct(private ItaDb $db, private int $storeId, private string $key)
    {
    }

    public function reserve(string $identity, string $address): void
    {
        $buckets = [hash_hmac('sha256','identity:'.$identity,$this->key)=>5,
            hash_hmac('sha256','address:'.$address,$this->key)=>30];
        ksort($buckets);
        $this->db->beginTransaction();
        try {
            foreach ($buckets as $hash=>$limit) {
                $this->db->execute('INSERT INTO ita_login_limits (store_id,bucket_hash,attempts,window_started,last_attempt)
                    VALUES (?,?,0,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE bucket_hash=bucket_hash', 'is', [$this->storeId,$hash]);
                $row = $this->db->fetchOne('SELECT attempts,UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(window_started) age
                    FROM ita_login_limits WHERE store_id=? AND bucket_hash=? FOR UPDATE', 'is', [$this->storeId,$hash]);
                if ((int) $row['age'] >= 900) {
                    $this->db->execute('UPDATE ita_login_limits SET attempts=0,window_started=UTC_TIMESTAMP() WHERE store_id=? AND bucket_hash=?', 'is', [$this->storeId,$hash]);
                } elseif ((int) $row['attempts'] >= $limit) {
                    throw new ItaHttpError(429, 'Đã vượt số lần đăng nhập. Vui lòng thử lại sau 15 phút.');
                }
                $this->db->execute('UPDATE ita_login_limits SET attempts=attempts+1,last_attempt=UTC_TIMESTAMP() WHERE store_id=? AND bucket_hash=?', 'is', [$this->storeId,$hash]);
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function clearIdentity(string $identity): void
    {
        $hash = hash_hmac('sha256', 'identity:'.$identity, $this->key);
        $this->db->execute('UPDATE ita_login_limits SET attempts=0 WHERE store_id=? AND bucket_hash=?', 'is', [$this->storeId,$hash]);
    }
}
