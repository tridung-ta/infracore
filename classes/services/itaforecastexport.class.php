<?php
class ItaForecastExport
{
    public static function table(array $document): array
    {
        if (count($document['rows'])>ItaForecastRules::MAX_ROWS) { throw new ItaHttpError(422,'Báo cáo vượt 1.000 dòng; không cắt ngầm.'); }
        $rows=[['Năm',$document['year'],'Tính tại',$document['as_of'],'Nguồn',!empty($document['saved'])?'Snapshot đã lưu':'Dự báo hiện tại'],
            ['Trạng thái',$document['summary']['complete']?'Đủ dữ liệu':'Chưa đủ dữ liệu — tổng phần đã tính'],
            ['Thiết bị chưa có bộ phận',$document['unassigned_assets'] ?? 0],
            ['Cửa sổ bảo trì UTC',$document['history_window']['start_utc'],$document['history_window']['end_utc']],
            ['Bộ phận','Loại thiết bị','Thay thế (VND)','Bảo trì (VND)','Mua thêm (VND)','Ghi chú / đầu vào']];
        foreach ($document['rows'] as $r) {
            $rows[]=[$r['department_name'],$r['category_name'],$r['replacement'] ?? 'Chưa đủ dữ liệu',$r['maintenance'] ?? 'Chưa đủ dữ liệu',$r['growth'] ?? 'Chưa đủ dữ liệu',
                implode('; ',$r['issues']).' | Giá: '.($r['inputs']['price'] ?? 'chưa có').'; thiết bị/người: '.($r['inputs']['units_per_person'] ?? 'chưa có')
                .'; nhân sự tăng: '.($r['inputs']['headcount']<0?'chưa lập kế hoạch':$r['inputs']['headcount']).'; tỷ lệ bảo trì dự phòng: '.($r['inputs']['maintenance_percent'] ?? 'chưa có')
                .'%; thiết bị: '.$r['asset_count'].'; thay thế: '.$r['replacement_count'].'; mua thêm: '.($r['growth_quantity'] ?? 'chưa xác định').'; phiếu lịch sử: '.$r['history_count']];
        }
        foreach (['replacement'=>'Thay thế','maintenance'=>'Bảo trì','growth'=>'Mua thêm','subtotal'=>'Cộng ba thành phần','contingency'=>'Dự phòng 10%','total'=>'Tổng phần đã tính (VND)'] as $key=>$label) {
            $rows[]=[$key==='total'&&$document['summary']['complete']?'Tổng ngân sách (VND)':$label,$document['summary'][$key]];
        }
        if (!empty($document['lifecycle_config']['using_defaults'])) { $rows[]=['Ngưỡng/trọng số vòng đời','Đang dùng giá trị mặc định']; }
        return $rows;
    }

    public static function render(array $document,string $format): string
    {
        $rows=self::table($document);
        return match($format) {
            'xlsx'=>ItaExcel::write(['Dự báo ngân sách CNTT — VND','','','','',''],$rows),
            'pdf'=>ItaForecastPdf::render($rows),
            default=>throw new ItaHttpError(422,'Chỉ hỗ trợ Excel hoặc PDF.'),
        };
    }
}
