<?php
/** Non-negative decimal arithmetic; no floats or optional arithmetic extension. */
class ItaForecastMoney
{
    public static function units(mixed $value,int $scale=2): string
    {
        if (!is_string($value) && !is_int($value)) { throw new ItaHttpError(422,'Số thập phân không hợp lệ.'); }
        if (!preg_match('/^(0|[1-9][0-9]{0,29})(?:\.([0-9]{1,6}))?$/D',(string)$value,$m)) {
            throw new ItaHttpError(422,'Số thập phân không hợp lệ.');
        }
        $fraction=$m[2] ?? '';
        $result=self::trim($m[1].substr(str_pad($fraction,$scale,'0'),0,$scale));
        return strlen($fraction)>$scale && $fraction[$scale]>='5' ? self::add($result,'1') : $result;
    }

    public static function trim(string $value): string { return ltrim($value,'0') ?: '0'; }

    public static function add(string $a,string $b): string
    {
        $a=strrev($a);$b=strrev($b);$out='';$carry=0;
        for ($i=0;$i<max(strlen($a),strlen($b));$i++) {
            $n=(int)($a[$i] ?? 0)+(int)($b[$i] ?? 0)+$carry;
            $out.=($n%10);$carry=intdiv($n,10);
        }
        $result=self::trim(strrev($out.($carry ?: '')));
        if (strlen($result)>36) { throw new ItaHttpError(422,'Ngân sách vượt giới hạn tính toán.'); }
        return $result;
    }

    public static function multiply(string $a,string $b): string
    {
        $result='0';
        foreach (str_split($b) as $digit) {
            $result=self::trim($result.'0');
            for ($i=0;$i<(int)$digit;$i++) { $result=self::add($result,$a); }
        }
        return $result;
    }

    public static function divide(string $a,int $divisor,string $mode='round'): string
    {
        if ($divisor<1 || $divisor>100000000) { throw new LogicException('Invalid divisor'); }
        $out='';$remainder=0;
        foreach (str_split($a) as $digit) {
            $n=$remainder*10+(int)$digit;$out.=intdiv($n,$divisor);$remainder=$n%$divisor;
        }
        $up=$mode==='ceil' ? $remainder>0 : ($mode==='round' && $remainder*2>=$divisor);
        return $up ? self::add(self::trim($out),'1') : self::trim($out);
    }

    public static function format(string $cents): string
    {
        $value=str_pad($cents,3,'0',STR_PAD_LEFT);
        return substr($value,0,-2).'.'.substr($value,-2);
    }

    public static function total(array $values): string
    {
        $out='0';
        foreach ($values as $v) { $out=self::add($out,self::units($v)); }
        return self::format($out);
    }
}
