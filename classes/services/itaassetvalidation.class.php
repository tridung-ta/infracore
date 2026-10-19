<?php
/** Pure validation; no database access or environment bootstrap. */
class ItaAssetValidation
{
    public const TEXT = ['asset_code'=>64,'name'=>200,'brand'=>100,'model'=>150,'serial_no'=>150,
        'specs'=>10000,'os'=>120,'vendor'=>200,'location'=>255];

    public static function fields(array $input): array
    {
        $values = [];
        foreach (self::TEXT as $field=>$length) {
            $values[$field] = ItaInput::text($input[$field] ?? '', $length);
        }
        if ($values['asset_code'] === '' || $values['name'] === '') {
            throw new ItaHttpError(422, 'Mã và tên tài sản không được để trống.');
        }
        $values['category_id'] = ItaInput::positiveInt($input['category_id'] ?? null);
        $department = $input['department_id'] ?? '';
        $values['department_id'] = $department === '' || $department === null ? null : ItaInput::positiveInt($department);
        foreach (['purchase_date','warranty_end'] as $field) {
            $date = ItaInput::text($input[$field] ?? '', 10);
            $values[$field] = $date === '' ? null : ItaInput::date($date)->format('Y-m-d');
            if ($values[$field] !== null && $values[$field] < '1000-01-01') {
                throw new ItaHttpError(422, 'Ngày tài sản phải từ năm 1000 trở đi.');
            }
        }
        $price = ItaInput::text($input['purchase_price'] ?? '', 22);
        if ($price !== '' && !preg_match('/^(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,2})?$/D', $price)) {
            throw new ItaHttpError(422, 'Giá mua phải là số không âm, tối đa 16 chữ số và 2 số thập phân.');
        }
        $values['purchase_price'] = $price === '' ? null : $price;
        if ($values['purchase_date'] && $values['warranty_end'] && $values['warranty_end'] < $values['purchase_date']) {
            throw new ItaHttpError(422, 'Ngày hết bảo hành không được trước ngày mua.');
        }
        return $values;
    }

    public static function status(string $before, string $after, string $role): void
    {
        $transitions = ['in_use'=>['maintenance','broken','retired'],
            'maintenance'=>['in_use','broken','retired'],'broken'=>['maintenance','retired'],'retired'=>[]];
        if (!in_array($after, $transitions[$before] ?? [], true)) {
            throw new ItaHttpError(422, 'Chuyển trạng thái không hợp lệ.');
        }
        if ($after === 'retired' && $role !== 'admin_it') {
            throw new ItaHttpError(403, 'Chỉ Admin IT được thực hiện thanh lý.');
        }
    }

    public static function warrantyRange(array $filters): void
    {
        $from = ($filters['warranty_from'] ?? '') === '' ? null : ItaInput::date($filters['warranty_from']);
        $to = ($filters['warranty_to'] ?? '') === '' ? null : ItaInput::date($filters['warranty_to']);
        if ($from && $to && $from > $to) {
            throw new ItaHttpError(422,'Khoảng bảo hành không hợp lệ: ngày bắt đầu phải trước hoặc bằng ngày kết thúc.');
        }
    }
}
