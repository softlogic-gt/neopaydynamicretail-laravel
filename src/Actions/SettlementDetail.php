<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

final class SettlementDetail extends PosAction
{
    protected string $endpoint = 'settlementdetail';

    public static function forBatch(int|string|null $batch = null): static
    {
        return new self($batch === null ? [] : ['privateUse60' => self::trace($batch)]);
    }
}
