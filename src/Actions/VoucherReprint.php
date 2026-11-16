<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

class VoucherReprint extends PosAction
{
    protected string $endpoint = 'authorizationvoucherreprint';

    public static function forTrace(int|string $trace): static
    {
        return new static(['systemsTraceNo' => static::trace($trace)]);
    }
}
