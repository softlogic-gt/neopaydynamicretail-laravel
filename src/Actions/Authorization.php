<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

final class Authorization extends PosAction
{
    protected string $endpoint = 'paymentcommerceauthorization';

    public static function sale(int|string $trace, float $amount, ?float $tip = null, string $additionalData = ''): static
    {
        return self::make('SA', $trace, $amount, $additionalData, $tip === null ? '' : self::decimals($tip));
    }

    public static function salePoints(int|string $trace, float $points): static
    {
        return self::make('SA', $trace, $points, 'LU');
    }

    public static function saleInstallments(int|string $trace, float $amount, int $installments): static
    {
        return self::make('SA', $trace, $amount, sprintf('VC%02d', self::count($installments, 'installments')));
    }

    public static function saleExtraFinancing(int|string $trace, float $amount, int $plan): static
    {
        return self::make('SA', $trace, $amount, sprintf('EF%02d', self::count($plan, 'plan')));
    }

    public static function saleDiscount(int|string $trace, float $amount, float $discount): static
    {
        return self::make('SA', $trace, $amount, sprintf('VD%012d', self::amount($discount)));
    }

    public static function annulment(int|string $trace): static
    {
        return self::make('AN', $trace);
    }

    public static function reverse(int|string $trace, float $amount): static
    {
        return self::make('RV', $trace, $amount);
    }

    public static function checkIn(int|string $trace, float $amount, int|string $folio): static
    {
        return self::make('CI', $trace, $amount, '', '', $folio);
    }

    public static function surcharge(int|string $trace, float $amount, int|string $folio): static
    {
        return self::make('RC', $trace, $amount, '', '', $folio);
    }

    public static function checkOut(int|string $trace, float $amount, int|string $folio, float $tip = 0, float $touristTax = 0): static
    {
        return self::make('CO', $trace, $amount, '', self::decimals($tip), $folio, self::decimals($touristTax));
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
            'systemsTraceNo' => self::trace($trace),
            'additionalData' => $additionalData,
            'amount' => [
                'amountTrans' => $amount === null ? '' : self::amount($amount),
                'additionalAmounts' => $tip,
            ],
        ];

        if ($folio !== null) {
            $body['privateUse63'] = [
                'lodgingFolioNumber14' => self::folio($folio),
                'taxAmount1' => $touristTax,
            ];
        }

        return new self($body);
    }
}
