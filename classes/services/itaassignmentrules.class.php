<?php
/** Pure rules shared by assignment writes and presentation. */
class ItaAssignmentRules
{
    public static function today(): string
    {
        return (new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
    }

    public static function overdue(array $asset): bool
    {
        $due = ItaMaintenanceSchedule::effective($asset);
        return $asset['status']!=='retired' && $due!==null && $due<self::today();
    }

    public static function assignable(array $asset, ?array $active, ItaUserObjectInfo $actor): void
    {
        if (!$actor->can('assignment.create')) {
            throw new ItaHttpError(403,'Bạn không có quyền tạo bàn giao.');
        }
        if ($asset['status']!=='in_use') {
            throw new ItaHttpError(409,'Chỉ bàn giao thiết bị đang sử dụng, không hỏng, bảo trì hoặc thanh lý.');
        }
        if ($active || $asset['current_user_id']!==null) {
            throw new ItaHttpError(409,'Thiết bị đang được giao. Cần thu hồi trước khi bàn giao mới.');
        }
    }

    public static function acknowledge(array $assignment, ItaUserObjectInfo $actor): void
    {
        if ($assignment['to_user_id']===null || (int) $assignment['to_user_id']!==$actor->id) {
            throw new ItaHttpError(403,'Chỉ chính người nhận thiết bị được xác nhận đã nhận.');
        }
        if ($assignment['returned_at']!==null || $assignment['acknowledged_at']!==null) {
            throw new ItaHttpError(409,'Lượt bàn giao đã được xác nhận hoặc đã thu hồi.');
        }
    }

    public static function dueDate(mixed $input): ?string
    {
        $value = ItaInput::text($input ?? '',10);
        if ($value==='') {
            return null;
        }
        $value = ItaInput::date($value)->format('Y-m-d');
        if ($value<self::today()) {
            throw new ItaHttpError(422,'Hạn thu hồi không được trước ngày bàn giao.');
        }
        return $value;
    }
}
