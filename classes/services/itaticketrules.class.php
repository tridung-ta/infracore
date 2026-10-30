<?php
class ItaTicketRules
{
    public static function today(): string
    {
        return (new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
    }

    public static function transition(string $from, string $to): void
    {
        if (!in_array($to,['pending'=>['resolved','failed'],'failed'=>['pending']][$from] ?? [],true)) {
            throw new ItaHttpError(422,'Chuyển trạng thái phiếu không hợp lệ.');
        }
    }

    public static function cost(mixed $value): string
    {
        $value = ItaInput::text($value,20);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,2})?$/D',$value)) {
            throw new ItaHttpError(422,'Chi phí phải là số VND không âm, tối đa 2 số thập phân.');
        }
        return $value;
    }

    public static function sameCost(string $a, string $b): bool
    {
        $normalize = static function (string $value): string {
            [$whole,$fraction] = array_pad(explode('.',$value,2),2,'');
            return (ltrim($whole,'0') ?: '0').'.'.str_pad($fraction,2,'0');
        };
        return $normalize($a)===$normalize($b);
    }

    public static function note(array $input): string
    {
        $note = ItaInput::text($input['note'] ?? '',4000);
        if ($note==='') {
            throw new ItaHttpError(422,'Vui lòng nhập nội dung hoặc lý do xử lý.');
        }
        return $note;
    }

    public static function handled(array $input, array $ticket): string
    {
        $date = ItaInput::date($input['handled_date'] ?? '')->format('Y-m-d');
        $opened = (new DateTimeImmutable($ticket['opened_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('Y-m-d');
        if ($date<$opened || $date>self::today()) {
            throw new ItaHttpError(422,'Ngày xử lý phải từ ngày mở phiếu đến hôm nay.');
        }
        return $date;
    }
}
