<?php
class ItaInput
{
    public static function positiveInt(mixed $value): int
    {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^[1-9][0-9]{0,8}$/D', (string) $value)) {
            throw new ItaHttpError(422, 'Mã định danh hoặc số trang không hợp lệ.');
        }
        return (int) $value;
    }

    public static function text(mixed $value, int $max = 200): string
    {
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $max || str_contains($value, "\0")) {
            throw new ItaHttpError(422, 'Dữ liệu yêu cầu không hợp lệ.');
        }
        return trim($value);
    }

    public static function date(mixed $value): DateTimeImmutable
    {
        $value = self::text($value, 10);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Ho_Chi_Minh'));
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new ItaHttpError(422, 'Ngày lọc không hợp lệ.');
        }
        return $date;
    }
}
