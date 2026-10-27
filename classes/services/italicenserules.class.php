<?php
class ItaLicenseRules
{
    public static function manage(ItaUserObjectInfo $actor): void
    {
        if (!$actor->can('license.read')) {
            throw new ItaHttpError(403,'Bạn không có quyền quản lý giấy phép.');
        }
    }

    public static function alert(?string $expiry, int $used, int $seats, ?string $today = null): array
    {
        $today ??= (new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        $days = $expiry ? (int) (new DateTimeImmutable($today))->diff(new DateTimeImmutable($expiry))->format('%r%a') : null;
        return ['days_remaining'=>$days,'expiry_level'=>$days===null?'none':($days<=7?'critical':($days<=30?'warning':'none')),
            'overallocated'=>$used>$seats,'usage_level'=>$used>$seats?'critical':'none'];
    }

    public static function excess(ItaUserObjectInfo $actor, int $used, int $seats, array $input): ?string
    {
        if ($used<=$seats) {
            return null;
        }
        $actor->requireCapability('license.overage');
        $reason = ItaInput::text($input['override_reason'] ?? '',1000);
        if (($input['confirm_overage'] ?? false)!==true || $reason==='') {
            throw new ItaHttpError(422,'Admin IT phải xác nhận vượt mức và nhập lý do.');
        }
        return $reason;
    }

    public static function fields(array $input): array
    {
        $name = ItaInput::text($input['software_name'] ?? '',200);
        $type = ItaInput::text($input['license_type'] ?? '',30);
        $status = ItaInput::text($input['status'] ?? '',10);
        $seats = ItaInput::positiveInt($input['total_seats'] ?? null);
        if ($name==='' || !in_array($type,['per_device','per_user','per_department'],true) || !in_array($status,['active','inactive'],true) || $seats>1000000) {
            throw new ItaHttpError(422,'Tên, loại, trạng thái hoặc số chỗ giấy phép không hợp lệ.');
        }
        $dates = [];
        foreach (['start_date','expire_date'] as $field) {
            $value = ItaInput::text($input[$field] ?? '',10);
            $dates[$field] = $value==='' ? null : ItaInput::date($value)->format('Y-m-d');
        }
        if ($dates['start_date'] && $dates['expire_date'] && $dates['expire_date']<$dates['start_date']) {
            throw new ItaHttpError(422,'Ngày hết hạn phải từ ngày bắt đầu trở đi.');
        }
        $cost = ItaInput::text($input['cost'] ?? '',20);
        if ($cost!=='' && !preg_match('/^\d{1,16}(\.\d{1,2})?$/D',$cost)) {
            throw new ItaHttpError(422,'Chi phí không hợp lệ.');
        }
        return ['software_name'=>$name,'vendor'=>ItaInput::text($input['vendor'] ?? '',200),'license_type'=>$type,
            'total_seats'=>$seats,'status'=>$status,'cost'=>$cost===''?null:$cost]+$dates;
    }
}
