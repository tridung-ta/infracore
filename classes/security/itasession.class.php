<?php
class ItaSession
{
    public static function start(array $config): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
        $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true);
        if (!$secure && !($local && filter_var($config['allow_local_http'], FILTER_VALIDATE_BOOLEAN))) {
            throw new ItaHttpError(400, 'Vui lòng truy cập bằng HTTPS để đăng nhập.');
        }
        session_name('ITA_SESSION');
        if (!session_start(['use_strict_mode'=>true,'use_only_cookies'=>true,'cookie_httponly'=>true,
            'cookie_secure'=>$secure,'cookie_samesite'=>'Lax','cookie_lifetime'=>0,'cookie_path'=>'/'])) {
            throw new RuntimeException('ITA session storage unavailable');
        }
        if (!isset($_SESSION['ita_csrf'])) {
            $_SESSION['ita_csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function requireCsrf(mixed $token): void
    {
        if (!is_string($token) || !hash_equals($_SESSION['ita_csrf'], $token)) {
            throw new ItaHttpError(403, 'Phiên thao tác đã hết hạn. Vui lòng tải lại trang.');
        }
    }

    public static function login(ItaUserObjectInfo $user): void
    {
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('ITA session rotation failed');
        }
        $_SESSION = ['ita_user_id'=>$user->id,'ita_store_id'=>$user->storeId,'ita_revision'=>$user->revision,
            'ita_started'=>time(),'ita_seen'=>time(),'ita_csrf'=>bin2hex(random_bytes(32))];
    }

    public static function valid(ItaUserObjectInfo $user): bool
    {
        return ($_SESSION['ita_store_id'] ?? 0) === $user->storeId
            && ($_SESSION['ita_revision'] ?? 0) === $user->revision
            && time() - ($_SESSION['ita_started'] ?? 0) < 28800
            && time() - ($_SESSION['ita_seen'] ?? 0) < 1800;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', ['expires'=>time()-3600,'path'=>'/','secure'=>$cookie['secure'],'httponly'=>true,'samesite'=>'Lax']);
        session_destroy();
    }
}
