<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Facades;

use Illuminate\Support\Facades\Facade;
use SoftlogicGT\NeoPayDynamicRetail\Pos;
use SoftlogicGT\NeoPayDynamicRetail\PosResponse;

/**
 * @method static PosResponse sale(int|string $trace, float $amount, ?float $tip = null, string $additionalData = '')
 * @method static PosResponse salePoints(int|string $trace, float $points)
 * @method static PosResponse saleInstallments(int|string $trace, float $amount, int $installments)
 * @method static PosResponse saleExtraFinancing(int|string $trace, float $amount, int $plan)
 * @method static PosResponse saleDiscount(int|string $trace, float $amount, float $discount)
 * @method static PosResponse annulment(int|string $trace)
 * @method static PosResponse reverse(int|string $trace, float $amount)
 * @method static PosResponse checkIn(int|string $trace, float $amount, int|string $folio)
 * @method static PosResponse surcharge(int|string $trace, float $amount, int|string $folio)
 * @method static PosResponse checkOut(int|string $trace, float $amount, int|string $folio, float $tip = 0, float $touristTax = 0)
 * @method static PosResponse settlement()
 * @method static PosResponse voucherReprint(int|string $trace)
 * @method static PosResponse sendDigitalVoucher(string $retrievalRefNo, ?string $type = null, ?string $to = null, ?string $clientName = null)
 * @method static PosResponse transactionDetail()
 * @method static PosResponse settlementDetail(int|string|null $batch = null)
 * @method static PosResponse settlementBatch()
 * @method static PosResponse reportTotals()
 *
 * @see Pos
 */
class NeoPayPos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Pos::class;
    }
}
