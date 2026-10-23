<?php
/** Minimal error boundary: never logs request bodies, credentials, URLs or exception messages. */
class ItaErrorResponse
{
    public static function report(int $status, string $kind = 'request'): string
    {
        try { $id=bin2hex(random_bytes(8)); }
        catch (Throwable) { $id='unavailable'; }
        self::log($id,$status,$kind);
        return $id;
    }

    private static function log(string $id, int $status, string $kind): void
    {
        try {
            error_log(json_encode(['event'=>'ita.error','request_id'=>$id,'status'=>$status,
                'kind'=>in_array($kind,['request','render','bootstrap','response_started'],true)?$kind:'request'],JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            // Error reporting cannot recursively invoke itself when the host logger fails.
        }
    }

    /** API/download errors stay JSON; HTML errors use Smarty with a dependency-free fallback. */
    public static function send(int $status, string $message, bool $json = false, ?string $id = null): void
    {
        $status=$status>=400 && $status<=599?$status:500;
        $id ??= self::report($status);
        if (headers_sent()) { self::log($id,$status,'response_started');return; }
        foreach (['Content-Disposition','Content-Length','Location'] as $name) { header_remove($name); }
        http_response_code($status);
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: '.($json?'application/json':'text/html').'; charset=utf-8');
        if ($status===429) { header('Retry-After: 900'); }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET')==='HEAD') { return; }
        if ($json) {
            echo json_encode(['success'=>false,'data'=>null,'message'=>$message,'request_id'=>$id],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
            return;
        }
        $level=ob_get_level();ob_start();
        try { $html=self::html($status,$message,$id); }
        catch (Throwable) {
            self::log($id,$status,'render');
            $html='<!doctype html><html lang="vi"><meta charset="utf-8"><title>InfraCore</title><h1>Yêu cầu chưa thể hoàn tất</h1><p>Mã tham chiếu: '.htmlspecialchars($id,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</p></html>';
        } finally {
            while (ob_get_level()>$level) { ob_end_clean(); }
        }
        echo $html;
    }

    private static function html(int $status, string $message, string $id): string
    {
        $root=defined('ROOT_PATH')?ROOT_PATH:dirname(__DIR__,2).'/';
        if (!is_file($root.'classes/template/smarty.class.php')) { throw new RuntimeException('Error template dependency unavailable'); }
        require_once $root.'classes/template/smarty.class.php';
        $directory=sys_get_temp_dir().'/infracore-error-'.substr(hash('sha256',$root),0,12);
        if (is_link($directory)) { throw new RuntimeException('Linked error cache refused'); }
        if (!is_dir($directory) && !mkdir($directory,0700,true) && !is_dir($directory)) { throw new RuntimeException('Error renderer unavailable'); }
        $cache=str_replace('\\','/',realpath($directory));$web=str_replace('\\','/',realpath($root));
        if (str_starts_with(strtolower($cache).'/',strtolower(rtrim($web,'/')).'/')) { throw new RuntimeException('Error cache must be private'); }
        $template=new Smarty();$template->setTemplateDir($root.'templates');$template->setCompileDir($directory);
        $title=match($status) { 403=>'Bạn chưa có quyền truy cập',404=>'Không tìm thấy nội dung',500=>'Chưa thể hoàn tất yêu cầu',503=>'Hệ thống chưa sẵn sàng',default=>'Yêu cầu chưa thể hoàn tất' };
        $template->assign(['errorStatus'=>$status,'errorTitle'=>$title,'errorMessage'=>$message,'errorReference'=>$id]);
        return $template->fetch('admin/ita/error.tpl.html');
    }
}
