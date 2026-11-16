<?php

namespace SoftlogicGT\NeoPayDynamicRetail;

use SoftlogicGT\NeoPayDynamicRetail\Actions\Authorization;
use SoftlogicGT\NeoPayDynamicRetail\Actions\ReportTotals;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SendDigitalVoucher;
use SoftlogicGT\NeoPayDynamicRetail\Actions\Settlement;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SettlementBatch;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SettlementDetail;
use SoftlogicGT\NeoPayDynamicRetail\Actions\TransactionDetail;
use SoftlogicGT\NeoPayDynamicRetail\Actions\VoucherReprint;

class Pos
{
    public function sale(int|string $trace, float $amount, ?float $tip = null, string $additionalData = ''): PosResponse
    {
        return Authorization::sale($trace, $amount, $tip, $additionalData)->submit();
    }

    public function salePoints(int|string $trace, float $points): PosResponse
    {
        return Authorization::salePoints($trace, $points)->submit();
    }

    public function saleInstallments(int|string $trace, float $amount, int $installments): PosResponse
    {
        return Authorization::saleInstallments($trace, $amount, $installments)->submit();
    }

    public function saleExtraFinancing(int|string $trace, float $amount, int $plan): PosResponse
    {
        return Authorization::saleExtraFinancing($trace, $amount, $plan)->submit();
    }

    public function saleDiscount(int|string $trace, float $amount, float $discount): PosResponse
    {
        return Authorization::saleDiscount($trace, $amount, $discount)->submit();
    }

    public function annulment(int|string $trace): PosResponse
    {
        return Authorization::annulment($trace)->submit();
    }

    public function reverse(int|string $trace, float $amount): PosResponse
    {
        return Authorization::reverse($trace, $amount)->submit();
    }

    public function checkIn(int|string $trace, float $amount, int|string $folio): PosResponse
    {
        return Authorization::checkIn($trace, $amount, $folio)->submit();
    }

    public function surcharge(int|string $trace, float $amount, int|string $folio): PosResponse
    {
        return Authorization::surcharge($trace, $amount, $folio)->submit();
    }

    public function checkOut(int|string $trace, float $amount, int|string $folio, float $tip = 0, float $touristTax = 0): PosResponse
    {
        return Authorization::checkOut($trace, $amount, $folio, $tip, $touristTax)->submit();
    }

    public function settlement(): PosResponse
    {
        return (new Settlement())->submit();
    }

    public function voucherReprint(int|string $trace): PosResponse
    {
        return VoucherReprint::forTrace($trace)->submit();
    }

    public function sendDigitalVoucher(string $retrievalRefNo, ?string $type = null, ?string $to = null, ?string $clientName = null): PosResponse
    {
        return SendDigitalVoucher::make($retrievalRefNo, $type, $to, $clientName)->submit();
    }

    public function transactionDetail(): PosResponse
    {
        return (new TransactionDetail())->submit();
    }

    public function settlementDetail(int|string|null $batch = null): PosResponse
    {
        return SettlementDetail::forBatch($batch)->submit();
    }

    public function settlementBatch(): PosResponse
    {
        return (new SettlementBatch())->submit();
    }

    public function reportTotals(): PosResponse
    {
        return (new ReportTotals())->submit();
    }
}
