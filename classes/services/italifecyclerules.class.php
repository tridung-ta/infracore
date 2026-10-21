<?php
/** Pure lifecycle rules. No database/configuration writes. */
class ItaLifecycleRules
{
    public const DEFAULTS = ['lifecycle_weight_age'=>'0.5','lifecycle_weight_repairs'=>'0.3','lifecycle_weight_cost'=>'0.2','lifecycle_replace_threshold'=>'0.8','lifecycle_watch_threshold'=>'0.6'];

    public static function settings(array $values): array
    {
        $result=[];$missing=[];
        foreach (self::DEFAULTS as $key=>$default) {
            if (!array_key_exists($key,$values)) { $missing[]=$key;$value=$default; } else { $value=$values[$key]; }
            if (!is_string($value) || !preg_match('/^(?:0|[1-9][0-9]?)(?:\.[0-9]{1,6})?$/D',$value)) {
                throw new ItaHttpError(422,'Cấu hình vòng đời không hợp lệ: '.$key);
            }
            $result[$key]=$value;
        }
        $units=fn(string $v)=>(int) str_replace('.','',str_pad(explode('.',$v)[0].'.'.(explode('.',$v)[1] ?? ''),strlen(explode('.',$v)[0])+7,'0'));
        $weights=array_map($units,array_slice($result,0,3));
        if (array_sum($weights)!==1000000 || min($weights)<0 || max($weights)>1000000
            || (float)$result['lifecycle_watch_threshold'] >= (float)$result['lifecycle_replace_threshold']
            || (float)$result['lifecycle_replace_threshold']>10) {
            throw new ItaHttpError(422,'Trọng số vòng đời phải có tổng bằng 1, ngưỡng theo dõi nhỏ hơn ngưỡng thay thế (tối đa 10).');
        }
        return ['values'=>$result,'default_keys'=>$missing,'using_defaults'=>(bool)$missing];
    }

    public static function window(string $today): array
    {
        $end=ItaInput::date($today);
        $target=$end->modify('first day of this month')->modify('-12 months');
        $start=$target->setDate((int)$target->format('Y'),(int)$target->format('m'),min((int)$end->format('d'),(int)$target->format('t')));
        return ['as_of'=>$today,'start_date'=>$start->format('Y-m-d'),
            'start_utc'=>$start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'end_utc'=>$end->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
    }

    public static function age(?string $purchase,string $today): ?float
    {
        if (!$purchase) { return null; }
        try { $date=ItaInput::date($purchase); } catch (ItaHttpError $error) { return null; }
        $asOf=ItaInput::date($today);
        return $date>$asOf ? null : ((int)$date->diff($asOf)->format('%a'))/365.25;
    }

    /** Pure oracle for tests/calculation examples; query uses high precision DECIMAL for ranking. */
    public static function score(array $input,array $config): array
    {
        $age=$input['age_years'] ?? null;$life=$input['lifespan_years'] ?? null;
        $price=$input['purchase_price'] ?? null;$broken=($input['status'] ?? '')==='broken';
        $ageRatio=$age!==null && $age>=0 && $life!==null && $life>0 ? $age/$life : null;
        $costRatio=$broken ? 1.0 : ($price!==null && $price>0 ? min((float)($input['repair_cost'] ?? 0)/(float)$price,1.0) : null);
        $parts=['age'=>$ageRatio===null?null:(float)$config['lifecycle_weight_age']*$ageRatio,
            'repairs'=>(float)$config['lifecycle_weight_repairs']*min((int)($input['repair_count'] ?? 0)/3,1),
            'cost'=>$costRatio===null?null:(float)$config['lifecycle_weight_cost']*$costRatio];
        $raw=$parts['age']===null||$parts['cost']===null?null:array_sum($parts);
        return ['score_raw'=>$raw,'age_ratio'=>$ageRatio,'parts'=>$parts,'tier'=>self::tier($raw,$config)];
    }

    public static function tier(?float $raw,array $config): string
    {
        if ($raw===null) { return 'missing'; }
        if ($raw >= (float)$config['lifecycle_replace_threshold']) { return 'replace'; }
        return $raw >= (float)$config['lifecycle_watch_threshold'] ? 'watch' : 'normal';
    }

    public static function display(?string $raw,array $config): ?string
    {
        if ($raw===null) { return null; }
        return number_format((float)$raw,3,'.','');
    }
}
