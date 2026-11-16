<?php

namespace SoftlogicGT\NeoPayDynamicRetail;

use Illuminate\Support\Facades\Log;

class PosLogger
{
    private const REDACTED = [
        'commerceVoucher', 'clientVoucher', 'instantWinner', 'pinPadVoucher',
        'settlementPrint', 'settlementPrintPinPad', 'reportDetailPrint', 'reportDetailPrintPinPad',
        'cardHolderName', 'clientName', 'to',
    ];

    private const LAST_FOUR = ['panPci', 'primaryAcctNum'];

    public function request(string $endpoint, array $body): void
    {
        $this->write('info', 'request', ['endpoint' => $endpoint, 'body' => $this->mask($body)]);
    }

    public function response(string $endpoint, array $data): void
    {
        $this->write('info', 'response', ['endpoint' => $endpoint, 'body' => $this->mask($data)]);
    }

    public function failure(string $endpoint, ?int $status): void
    {
        $this->write('error', 'failure', ['endpoint' => $endpoint, 'status' => $status]);
    }

    public function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->mask($value);
            } elseif (in_array($key, self::REDACTED, true)) {
                $data[$key] = '***';
            } elseif (in_array($key, self::LAST_FOUR, true)) {
                $data[$key] = '***'.substr((string) $value, -4);
            }
        }

        return $data;
    }

    private function write(string $level, string $event, array $context): void
    {
        $channel = config('neopay-dynamic-retail.log_channel');

        if ($channel) {
            Log::channel($channel)->{$level}("neopay-dynamic-retail {$event}", $context);
        }
    }
}
