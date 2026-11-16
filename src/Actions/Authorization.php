<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

class Authorization extends PosAction
{
    protected string $endpoint = 'paymentcommerceauthorization';

    public static function sale(int|string $trace, float $amount, ?float $tip = null, string $additionalData = ''): static
    {
        return static::make('SA', $trace, $amount, $additionalData, $tip === null ? '' : static::decimals($tip));
    }

    public static function salePoints(int|string $trace, float $points): static
    {
        return static::make('SA', $trace, $points, 'LU');
    }

    public static function saleInstallments(int|string $trace, float $amount, int $installments): static
    {
        return static::make('SA', $trace, $amount, sprintf('VC%02d', $installments));
    }

    public static function saleExtraFinancing(int|string $trace, float $amount, int $plan): static
    {
        return static::make('SA', $trace, $amount, sprintf('EF%02d', $plan));
    }

    public static function saleDiscount(int|string $trace, float $amount, float $discount): static
    {
        return static::make('SA', $trace, $amount, sprintf('VD%012d', static::amount($discount)));
    }

    public static function annulment(int|string $trace): static
    {
        return static::make('AN', $trace);
    }

    public static function reverse(int|string $trace, float $amount): static
    {
        return static::make('RV', $trace, $amount);
    }

    public static function checkIn(int|string $trace, float $amount, int|string $folio): static
    {
        return static::make('CI', $trace, $amount, '', '', $folio);
    }

    public static function surcharge(int|string $trace, float $amount, int|string $folio): static
    {
        return static::make('RC', $trace, $amount, '', '', $folio);
    }

    public static function checkOut(int|string $trace, float $amount, int|string $folio, float $tip = 0, float $touristTax = 0): static
    {
        return static::make('CO', $trace, $amount, '', static::decimals($tip), $folio, static::decimals($touristTax));
    }

    private static function make(
        string $type,
        int|string $trace,
        ?float $amount = null,
        string $additionalData = '',
        string $tip = '',
        int|string|null $folio = null,
        string $touristTax = '',
    ): static {
        $body = [
            'transactionType' => $type,
            'systemsTraceNo' => static::trace($trace),
            'additionalData' => $additionalData,
            'amount' => [
                'amountTrans' => $amount === null ? '' : static::amount($amount),
                'additionalAmounts' => $tip,
            ],
        ];

        if ($folio !== null) {
            $body['privateUse63'] = [
                'lodgingFolioNumber14' => static::folio($folio),
                'taxAmount1' => $touristTax,
            ];
        }

        return new static($body);
    }
}
