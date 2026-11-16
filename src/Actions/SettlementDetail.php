<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

class SettlementDetail extends PosAction
{
    protected string $endpoint = 'settlementdetail';

    public static function forBatch(int|string|null $batch = null): static
    {
        return new static($batch === null ? [] : ['privateUse60' => static::trace($batch)]);
    }
}
