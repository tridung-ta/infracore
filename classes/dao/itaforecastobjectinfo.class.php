<?php
class ItaForecastObjectInfo
{
    public static function version(?array $row): string
    {
        return hash('sha256',json_encode($row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    }

    public static function snapshot(array $plan): array
    {
        if (strlen($plan['calculation_snapshot_json'] ?? '')>2000000) { throw new ItaHttpError(409,'Snapshot vượt giới hạn.'); }
        try { $data=json_decode($plan['calculation_snapshot_json'] ?? '',true,32,JSON_THROW_ON_ERROR); }
        catch (Throwable $e) { throw new ItaHttpError(409,'Kế hoạch chưa có snapshot hợp lệ; không tự dùng giá hiện tại.'); }
        if (!is_array($data) || ($data['schema_version'] ?? null)!==1 || !is_array($data['rows'] ?? null)
            || (int)($data['year'] ?? 0)!==(int)$plan['year'] || count($data['rows'])>ItaForecastRules::MAX_ROWS) {
            throw new ItaHttpError(409,'Snapshot không đúng phiên bản hoặc năm.');
        }
        if (!is_array($data['summary'] ?? null) || !is_string($data['as_of'] ?? null) || !is_array($data['history_window'] ?? null)) {
            throw new ItaHttpError(409,'Snapshot thiếu thông tin tổng hợp.');
        }
        foreach ($data['rows'] as $row) {
            if (!is_array($row) || (int)($row['department_id'] ?? 0)!==(int)$plan['department_id'] || !is_array($row['issues'] ?? null) || !is_array($row['inputs'] ?? null)) {
                throw new ItaHttpError(409,'Snapshot không đúng cấu trúc hoặc bộ phận.');
            }
            foreach (['category_id','department_name','category_name','asset_count','replacement_count','growth_quantity'] as $key) {
                if (!array_key_exists($key,$row)) { throw new ItaHttpError(409,'Snapshot thiếu dữ liệu dòng.'); }
            }
            if (!is_array($row['asset_inputs'] ?? null) || count($row['asset_inputs'])!==$row['asset_count']) {
                throw new ItaHttpError(409,'Snapshot thiếu đầu vào thiết bị.');
            }
            foreach (['replacement','maintenance','growth'] as $key) {
                if (!array_key_exists($key,$row) || ($row[$key]!==null && (!is_string($row[$key]) || !preg_match('/^(0|[1-9][0-9]{0,33})\.[0-9]{2}$/D',$row[$key])))) {
                    throw new ItaHttpError(409,'Snapshot có số tiền không hợp lệ.');
                }
                if ($row[$key]===null && !$row['issues']) { throw new ItaHttpError(409,'Snapshot thiếu lý do số liệu chưa đầy đủ.'); }
            }
        }
        $expected=ItaForecastRules::summary($data['rows']);
        if (!empty($data['unassigned_assets'])) { $expected['complete']=false; }
        if ($data['summary']!==$expected) { throw new ItaHttpError(409,'Tổng snapshot không khớp các thành phần.'); }
        return array_replace($data,['plan_id'=>(int)$plan['id'],'department_id'=>(int)$plan['department_id'],'status'=>$plan['status'],'version'=>self::version($plan),
            'planned_headcount_increase'=>(int)$plan['planned_headcount_increase'],'note'=>$plan['note'],'saved'=>true]);
    }
}
