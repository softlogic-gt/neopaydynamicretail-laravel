<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

final class VoucherReprint extends PosAction
{
    protected string $endpoint = 'authorizationvoucherreprint';

    public static function forTrace(int|string $trace): static
    {
        return new self(['systemsTraceNo' => self::trace($trace)]);
    }
}
