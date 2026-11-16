<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

class SendDigitalVoucher extends PosAction
{
    protected string $endpoint = 'SendDigitalVoucher';

    public static function make(string $retrievalRefNo, ?string $type = null, ?string $to = null, ?string $clientName = null): static
    {
        return new static(array_filter([
            'retrivalRefNo' => $retrievalRefNo,
            'clientName' => $clientName,
            'notification' => $type && $to ? ['type' => $type, 'to' => $to] : null,
        ]));
    }
}
