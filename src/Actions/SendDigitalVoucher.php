<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

final class SendDigitalVoucher extends PosAction
{
    protected string $endpoint = 'SendDigitalVoucher';

    public static function make(string $retrievalRefNo, ?string $type = null, ?string $to = null, ?string $clientName = null): static
    {
        return new self(array_filter([
            'retrivalRefNo' => $retrievalRefNo,
            'clientName' => $clientName,
            'notification' => $type && $to ? ['type' => $type, 'to' => $to] : null,
        ]));
    }
}
