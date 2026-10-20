<?php
/** Pure document renderer. Text only; all image/HTTP/local asset loading is denied. */
class ItaHandoverPdf
{
    public static function available(): bool
    {
        return is_file(ROOT_PATH.'classes/ita-pdf/vendor/autoload.php') && extension_loaded('gd') && extension_loaded('mbstring');
    }

    public static function render(array $document): string
    {
        if (!self::available()) {
            throw new ItaHttpError(503,'Chưa sẵn sàng xuất PDF. Cần bộ thư viện PDF và PHP GD/mbstring.');
        }
        require_once ROOT_PATH.'classes/ita-pdf/vendor/autoload.php';
        $directory = self::temporaryDirectory();
        try {
            $http = new class implements \Mpdf\Http\ClientInterface {
                public function sendRequest(\Psr\Http\Message\RequestInterface $request) { throw new RuntimeException('PDF external resource denied'); }
            };
            $local = new class implements \Mpdf\File\LocalContentLoaderInterface {
                public function load($path) { throw new RuntimeException('PDF local asset denied'); }
            };
            $pdf = new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4','default_font'=>'dejavusans','default_font_size'=>10,
                'margin_left'=>18,'margin_right'=>18,'margin_top'=>18,'margin_bottom'=>18,'tempDir'=>$directory,
                'whitelistStreamWrappers'=>[],'showImageErrors'=>false],
                new \Mpdf\Container\SimpleContainer(['httpClient'=>$http,'localContentLoader'=>$local]));
            $pdf->SetTitle('Biên bản bàn giao thiết bị CNTT');
            $pdf->SetAuthor('InfraCore');
            $pdf->SetHTMLFooter('<div style="font-size:8pt;text-align:right">InfraCore | Trang {PAGENO}/{nbpg}</div>');
            $pdf->WriteHTML(self::html($document,$directory));
            return $pdf->Output('','S');
        } finally {
            self::clean($directory,$directory);
        }
    }

    public static function html(array $document, string $compileDirectory): string
    {
        require_once ROOT_PATH.'classes/template/smarty.class.php';
        $template = new Smarty();
        $template->setTemplateDir(ROOT_PATH.'templates');
        $template->setCompileDir($compileDirectory);
        $template->assign('receipt',$document);
        return $template->fetch('admin/ita/handoverpdf.tpl.html');
    }

    private static function temporaryDirectory(): string
    {
        $path = sys_get_temp_dir().'/infracore-pdf-'.bin2hex(random_bytes(16));
        if (!mkdir($path,0700)) {
            throw new RuntimeException('PDF temporary storage unavailable');
        }
        $root = strtolower(str_replace('\\','/',rtrim(realpath(ROOT_PATH),'/\\'))).'/';
        if (str_starts_with(strtolower(str_replace('\\','/',realpath($path))).'/',$root)) {
            rmdir($path);
            throw new RuntimeException('PDF storage must be outside webroot');
        }
        return $path;
    }

    /** Delete only this call's verified temp tree; never follow links. */
    private static function clean(string $path, string $base): void
    {
        $real = realpath($path);
        $baseReal = realpath($base);
        if ($real===false || $baseReal===false || ($real!==$baseReal && !str_starts_with($real,$baseReal.DIRECTORY_SEPARATOR))) {
            return;
        }
        foreach (scandir($path) ?: [] as $name) {
            if ($name==='.' || $name==='..') continue;
            $child = $path.DIRECTORY_SEPARATOR.$name;
            if (is_dir($child) && !is_link($child)) self::clean($child,$base);
            else @unlink($child);
        }
        @rmdir($path);
    }
}
