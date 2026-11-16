<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use SoftlogicGT\NeoPayDynamicRetail\Actions\Authorization;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SendDigitalVoucher;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SettlementDetail;
use SoftlogicGT\NeoPayDynamicRetail\Actions\VoucherReprint;
use SoftlogicGT\NeoPayDynamicRetail\Tests\TestCase;

class ValidationTest extends TestCase
{
    public static function invalidCalls(): array
    {
        return [
            'zero amount' => [fn () => Authorization::sale(1, 0)],
            'negative amount' => [fn () => Authorization::sale(1, -5)],
            'amount over 12 digits' => [fn () => Authorization::sale(1, 10_000_000_000)],
            'negative tip' => [fn () => Authorization::sale(1, 10, -1)],
            'zero points' => [fn () => Authorization::salePoints(1, 0)],
            'zero discount' => [fn () => Authorization::saleDiscount(1, 10, 0)],
            'zero reverse amount' => [fn () => Authorization::reverse(1, 0)],
            'installments zero' => [fn () => Authorization::saleInstallments(1, 10, 0)],
            'installments over 99' => [fn () => Authorization::saleInstallments(1, 10, 100)],
            'plan zero' => [fn () => Authorization::saleExtraFinancing(1, 10, 0)],
            'trace with letters' => [fn () => Authorization::annulment('12A')],
            'trace over 6 digits' => [fn () => Authorization::annulment(1234567)],
            'negative trace' => [fn () => Authorization::annulment(-1)],
            'empty trace' => [fn () => Authorization::annulment('')],
            'folio with letters' => [fn () => Authorization::checkIn(1, 10, 'F-1')],
            'folio over 12 digits' => [fn () => Authorization::checkIn(1, 10, '1234567890123')],
            'negative tourist tax' => [fn () => Authorization::checkOut(1, 10, 1, 0, -1)],
            'reprint bad trace' => [fn () => VoucherReprint::forTrace('x')],
            'batch bad number' => [fn () => SettlementDetail::forBatch('x')],
            'voucher bad reference' => [fn () => SendDigitalVoucher::make('abc')],
            'voucher type without recipient' => [fn () => SendDigitalVoucher::make('1', 'Email')],
            'voucher recipient without type' => [fn () => SendDigitalVoucher::make('1', null, 'a@b.com')],
        ];
    }

    #[DataProvider('invalidCalls')]
    public function test_invalid_input_is_rejected_before_any_request(callable $call): void
    {
        $this->expectException(InvalidArgumentException::class);

        $call();
    }

    public function test_boundary_values_are_accepted(): void
    {
        $this->fake();

        Authorization::sale('000001', 0.01, 0)->submit();
        Authorization::sale(999999, 9_999_999_999.99)->submit();
        Authorization::saleInstallments(1, 10, 99)->submit();
        Authorization::checkIn(1, 10, '999999999999')->submit();

        $this->assertCount(4, $this->posRequests());
        $this->assertSame('000001', $this->posRequests()[0]['systemsTraceNo']);
        $this->assertSame('999999999999', $this->posRequests()[1]['amount']['amountTrans']);
    }
}
