<?php
/** Immutable staging files outside the document root, addressed only by server-generated IDs. */
class ItaImportStorage
{
    private string $directory;

    public function __construct()
    {
        $this->directory = sys_get_temp_dir().'/infracore-import-'.substr(hash('sha256',ROOT_PATH),0,16);
        if (is_link($this->directory) || (!is_dir($this->directory) && !mkdir($this->directory,0700,true) && !is_dir($this->directory))) {
            throw new RuntimeException('Private staging unavailable');
        }
        $root = str_replace('\\','/',realpath(ROOT_PATH));
        $real = str_replace('\\','/',realpath($this->directory));
        if (str_starts_with(strtolower($real).'/',strtolower(rtrim($root,'/')).'/')) {
            throw new RuntimeException('Staging must be outside document root');
        }
    }

    public function put(array $rows): string
    {
        $json = json_encode($rows,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($json)>32*1024*1024) {
            throw new ItaHttpError(422,'Nội dung sau kiểm tra quá lớn. Hãy chia nhỏ file Excel.');
        }
        $name = bin2hex(random_bytes(24)).'.json';
        $handle = fopen($this->directory.'/'.$name,'x');
        if (!$handle) {
            throw new RuntimeException('Staging write unavailable');
        }
        try {
            if (fwrite($handle,$json)!==strlen($json) || !fflush($handle)) {
                throw new RuntimeException('Incomplete staging write');
            }
        } finally {
            fclose($handle);
        }
        return $name;
    }

    public function get(string $name): array
    {
        if (!preg_match('/^[a-f0-9]{48}\.json$/D',$name) || is_link($this->directory.'/'.$name)) {
            throw new ItaHttpError(410,'Bản xem trước không còn khả dụng. Hãy tải lại file.');
        }
        $path = $this->directory.'/'.$name;
        if (!is_file($path) || filesize($path)>32*1024*1024) {
            throw new ItaHttpError(410,'Bản xem trước không còn khả dụng. Hãy tải lại file.');
        }
        $rows = json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR);
        if (!is_array($rows) || !array_is_list($rows) || !$rows || count($rows)>ItaExcel::MAX_ROWS) {
            throw new ItaHttpError(410,'Dữ liệu xem trước không hợp lệ.');
        }
        return $rows;
    }
}
