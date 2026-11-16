<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests\Unit;

use SoftlogicGT\NeoPayDynamicRetail\Actions\ReportTotals;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SendDigitalVoucher;
use SoftlogicGT\NeoPayDynamicRetail\Actions\Settlement;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SettlementBatch;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SettlementDetail;
use SoftlogicGT\NeoPayDynamicRetail\Actions\TransactionDetail;
use SoftlogicGT\NeoPayDynamicRetail\Actions\VoucherReprint;
use SoftlogicGT\NeoPayDynamicRetail\Tests\TestCase;

class ActionsTest extends TestCase
{
    public function test_merchant_only_actions_hit_their_endpoint_with_merchant_data(): void
    {
        $this->fake();

        foreach ([
            Settlement::class => 'paymentcommercesettlement',
            TransactionDetail::class => 'transactiondetail',
            SettlementBatch::class => 'settlementbatch',
            ReportTotals::class => 'reporttotals',
        ] as $class => $endpoint) {
            (new $class)->submit();
        }

        $urls = array_map(fn ($r) => $r->url(), $this->posRequests());
        $this->assertSame([
            'https://pos.test/api/paymentcommercesettlement',
            'https://pos.test/api/transactiondetail',
            'https://pos.test/api/settlementbatch',
            'https://pos.test/api/reporttotals',
        ], $urls);

        foreach ($this->posRequests() as $request) {
            $this->assertSame(['terminalId' => 'T1', 'cardAcqId' => 'A1'], $request['merchant']);
        }
    }

    public function test_settlement_detail_with_and_without_batch(): void
    {
        $this->fake();

        SettlementDetail::forBatch(243)->submit();
        SettlementDetail::forBatch()->submit();

        [$withBatch, $latest] = $this->posRequests();
        $this->assertSame('settlementdetail', basename($withBatch->url()));
        $this->assertSame('000243', $withBatch['privateUse60']);
        $this->assertArrayNotHasKey('privateUse60', $latest->data());
    }

    public function test_voucher_reprint_sends_padded_trace(): void
    {
        $this->fake();

        VoucherReprint::forTrace(232)->submit();

        $request = $this->posRequests()[0];
        $this->assertSame('authorizationvoucherreprint', basename($request->url()));
        $this->assertSame('000232', $request['systemsTraceNo']);
    }

    public function test_send_digital_voucher_body(): void
    {
        $this->fake();

        SendDigitalVoucher::make('500815000512', 'Email', 'a@b.com', 'Juan')->submit();
        SendDigitalVoucher::make('500815000512')->submit();

        [$full, $minimal] = $this->posRequests();
        $this->assertSame('SendDigitalVoucher', basename($full->url()));
        $this->assertSame('500815000512', $full['retrivalRefNo']);
        $this->assertSame('Juan', $full['clientName']);
        $this->assertSame(['type' => 'Email', 'to' => 'a@b.com'], $full['notification']);
        $this->assertArrayNotHasKey('notification', $minimal->data());
        $this->assertArrayNotHasKey('clientName', $minimal->data());
    }
}
