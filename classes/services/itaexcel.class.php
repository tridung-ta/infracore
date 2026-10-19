<?php
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/** Bounded XLSX transport. No calculation, macros, external relationships or database access. */
class ItaExcel
{
    public const HEADERS = ['asset_code','category_code','name','serial_no','purchase_date','department_code','status',
        'purchase_price','warranty_end','brand','model','specs','os','vendor','location','status_reason'];
    public const MAX_ROWS = 5000;

    private static function load(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        spl_autoload_register(static function (string $class): void {
            if (!preg_match('/^(?:PhpOffice\\\\PhpSpreadsheet|MyCLabs|Psr|ZipStream)\\\\[a-zA-Z0-9_\\\\]+$/D',$class)) {
                return;
            }
            $path = ROOT_PATH.'classes/PhpSpreadSheet/'.str_replace('\\','/',$class).'.php';
            if (is_file($path)) {
                require_once $path;
            }
        });
        $loaded = true;
    }

    public static function read(string $path, string $name): array
    {
        self::inspect($path, $name);
        self::load();
        $reader = new Xlsx();
        $reader->setReadDataOnly(false);
        $info = $reader->listWorksheetInfo($path);
        if (count($info) !== 1 || $info[0]['totalRows'] > self::MAX_ROWS+1 || $info[0]['totalColumns'] > count(self::HEADERS)) {
            throw new ItaHttpError(422,'File cần đúng một sheet, tối đa 5.000 dòng dữ liệu và 16 cột theo mẫu.');
        }
        self::memoryBudget($info[0]['totalRows']*count(self::HEADERS));
        $book = $reader->load($path);
        try {
            return self::rows($book->getActiveSheet());
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private static function rows($sheet): array
    {
        $headers = [];
        for ($column=1; $column<=count(self::HEADERS); $column++) {
            $headers[] = self::cell($sheet, $column, 1);
        }
        if (array_slice($headers,0,7) !== array_slice(self::HEADERS,0,7)) {
            throw new ItaHttpError(422,'Bảy cột đầu phải đúng thứ tự và tên trong mẫu Excel.');
        }
        foreach ($headers as $index=>$header) {
            if ($header !== '' && $header !== self::HEADERS[$index]) {
                throw new ItaHttpError(422,'Cột tùy chọn sai tên hoặc sai vị trí. Hãy dùng file mẫu.');
            }
        }
        $rows = [];
        for ($line=2; $line<=$sheet->getHighestDataRow(); $line++) {
            $values = [];
            foreach (self::HEADERS as $index=>$header) {
                $values[$header] = self::cell($sheet,$index+1,$line);
                if ($headers[$index] === '' && $values[$header] !== '') {
                    throw new ItaHttpError(422,'Có dữ liệu trong cột không có tiêu đề.');
                }
            }
            if (implode('', $values) !== '') {
                $rows[] = ['line'=>$line,'values'=>$values];
            }
        }
        if (!$rows) {
            throw new ItaHttpError(422,'File không có dòng dữ liệu.');
        }
        return $rows;
    }

    private static function cell($sheet, int $column, int $line): string
    {
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column).$line);
        if ($cell->getDataType() === DataType::TYPE_FORMULA || $cell->getDataType() === DataType::TYPE_ERROR) {
            throw new ItaHttpError(422,'File có công thức hoặc ô lỗi. Hãy dán dữ liệu dưới dạng giá trị.');
        }
        $value = $cell->getValue();
        if ($value === null) {
            return '';
        }
        if (is_object($value) || is_bool($value)) {
            throw new ItaHttpError(422,'Chỉ chấp nhận ô văn bản hoặc số thông thường.');
        }
        if (in_array(self::HEADERS[$column-1],['purchase_date','warranty_end'],true) && is_numeric($value)
            && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        return ItaInput::text((string) $value,10000);
    }

    private static function inspect(string $path, string $name): void
    {
        if (!preg_match('/\.xlsx$/iD',$name) || !is_file($path) || filesize($path)<1 || filesize($path)>5*1024*1024) {
            throw new ItaHttpError(422,'Chọn file .xlsx có dữ liệu, tối đa 5 MB.');
        }
        $mime = class_exists('finfo',false) ? (new finfo(FILEINFO_MIME_TYPE))->file($path) : null;
        if ($mime!==null && !in_array($mime,['application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],true)) {
            throw new ItaHttpError(422,'Nội dung file không phải XLSX.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new ItaHttpError(422,'Không mở được file Excel.');
        }
        try {
            if ($zip->numFiles>200 || $zip->locateName('xl/workbook.xml')===false || $zip->locateName('[Content_Types].xml')===false) {
                throw new ItaHttpError(422,'Cấu trúc XLSX không hợp lệ.');
            }
            $total = 0;
            $names = [];
            for ($i=0; $i<$zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $key = strtolower($entry['name']);
                if (isset($names[$key])) {
                    throw new ItaHttpError(422,'File có thành phần ZIP trùng tên.');
                }
                $names[$key] = true;
                $total += $entry['size'];
                self::inspectEntry($zip,$entry,$total);
            }
        } finally {
            $zip->close();
        }
    }

    private static function inspectEntry(ZipArchive $zip, array $entry, int $total): void
    {
        $name = $entry['name'];
        if ($total>32*1024*1024 || $entry['size']>24*1024*1024 || ($entry['size']>1048576 && $entry['size']/max(1,$entry['comp_size'])>200)
            || ($entry['encryption_method'] ?? 0)!==0 || str_contains($name,'..') || str_contains($name,'\\') || str_starts_with($name,'/')
            || preg_match('~vba|externalLinks|embeddings|activeX|\.bin$~i',$name)) {
            throw new ItaHttpError(422,'File vượt giới hạn an toàn hoặc chứa nội dung không được hỗ trợ.');
        }
        if (preg_match('/\.(xml|rels)$/iD',$name)) {
            $xml = $zip->getFromName($name);
            if ($xml===false || str_contains($xml,"\0") || preg_match('~<!DOCTYPE|<!ENTITY|TargetMode\s*=\s*["\x27]External|macroEnabled|vbaProject~i',$xml)) {
                throw new ItaHttpError(422,'Không hỗ trợ XML mở rộng, macro hoặc liên kết ngoài.');
            }
            if (strtolower($name)==='xl/styles.xml' && preg_match_all('/<(?:[a-zA-Z0-9_]+:)?(?:xf|font|fill|border|numFmt)\b/',$xml)>2048) {
                throw new ItaHttpError(422,'File có quá nhiều định dạng. Hãy dán dữ liệu vào mẫu mới.');
            }
            if (str_ends_with(strtolower($name),'.rels')) {
                self::relationships($xml);
            }
        }
    }

    private static function relationships(string $xml): void
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->loadXML($xml,LIBXML_NONET)) {
                throw new ItaHttpError(422,'Quan hệ trong file Excel không hợp lệ.');
            }
            foreach ($document->getElementsByTagName('Relationship') as $relationship) {
                $target = $relationship->getAttribute('Target');
                if (strcasecmp($relationship->getAttribute('TargetMode'),'external')===0
                    || preg_match('~^[a-z][a-z0-9+.-]*:|^//~i',$target)) {
                    throw new ItaHttpError(422,'Không hỗ trợ liên kết ngoài trong file Excel.');
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** Explicit text preserves identifiers and prevents spreadsheet formula injection. */
    public static function write(array $headers, array $rows): string
    {
        self::load();
        self::memoryBudget((count($rows)+1)*count($headers));
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Tai san');
        $file = tempnam(sys_get_temp_dir(),'ita-xlsx-');
        try {
            foreach ([$headers,...$rows] as $index=>$row) {
                foreach (array_values($row) as $column=>$value) {
                    $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($column+1).($index+1),(string) ($value ?? ''),DataType::TYPE_STRING);
                }
            }
            $end = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$end.(count($rows)+1));
            $sheet->getStyle('A1:'.$end.'1')->getFont()->setBold(true);
            $sheet->getStyle('A1:'.$end.(count($rows)+1))->getAlignment()->setWrapText(true);
            foreach ($headers as $index=>$header) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index+1))->setWidth($header==='name' || $header==='error' ? 45 : 24);
            }
            (new XlsxWriter($book))->save($file);
            $bytes = file_get_contents($file);
            if ($bytes===false) {
                throw new RuntimeException('Excel output unavailable');
            }
            return $bytes;
        } finally {
            $book->disconnectWorksheets();
            if (is_string($file) && is_file($file)) {
                unlink($file);
            }
        }
    }

    private static function memoryBudget(int $cells): void
    {
        $limit = trim(ini_get('memory_limit'));
        if ($limit==='-1') {
            return;
        }
        $unit = strtolower(substr($limit,-1));
        $bytes = (int) $limit * match ($unit) {'g'=>1073741824,'m'=>1048576,'k'=>1024,default=>1};
        if ($bytes>0 && memory_get_usage(true)+$cells*4096+16777216>$bytes) {
            throw new ItaHttpError(422,'File cần nhiều bộ nhớ hơn giới hạn máy chủ. Hãy chia nhỏ file hoặc thu hẹp bộ lọc xuất.');
        }
    }
}
