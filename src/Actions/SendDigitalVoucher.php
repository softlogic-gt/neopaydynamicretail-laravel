<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

use InvalidArgumentException;

final class SendDigitalVoucher extends PosAction
{
    protected string $endpoint = 'SendDigitalVoucher';

    public static function make(string $retrievalRefNo, ?string $type = null, ?string $to = null, ?string $clientName = null): static
    {
        if (($type === null) !== ($to === null)) {
            throw new InvalidArgumentException('Notification type and recipient must be sent together.');
        }

        return new self(array_filter([
            'retrivalRefNo' => self::reference($retrievalRefNo),
            'clientName' => $clientName,
            'notification' => $type !== null ? ['type' => $type, 'to' => $to] : null,
        ]));
    }
}
