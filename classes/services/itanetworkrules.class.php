<?php
class ItaNetworkRules
{
    public static function access(ItaUserObjectInfo $actor): void
    {
        if (!$actor->can('network.read')) {
            throw new ItaHttpError(403,'Bạn không có quyền xem hạ tầng mạng.');
        }
    }

    public static function values(array $input): array
    {
        $a=ItaInput::positiveInt($input['source_asset_id'] ?? null);
        $b=ItaInput::positiveInt($input['target_asset_id'] ?? null);
        if ($a===$b) { throw new ItaHttpError(422,'Hai đầu liên kết phải khác nhau.'); }
        $type=ItaInput::text($input['link_type'] ?? '',16);
        if (!in_array($type,['ethernet','fiber','wireless'],true)) { throw new ItaHttpError(422,'Loại liên kết không hợp lệ.'); }
        $source=ItaInput::text($input['source_port'] ?? '',64);
        $target=ItaInput::text($input['target_port'] ?? '',64);
        if ($a>$b) { [$a,$b]=[$b,$a];[$source,$target]=[$target,$source]; }
        return ['source_asset_id'=>$a,'target_asset_id'=>$b,'link_type'=>$type,'source_port'=>$source,'target_port'=>$target,
            'capacity_label'=>ItaInput::text($input['capacity_label'] ?? '',64),'note'=>ItaInput::text($input['note'] ?? '',1000)];
    }
}
