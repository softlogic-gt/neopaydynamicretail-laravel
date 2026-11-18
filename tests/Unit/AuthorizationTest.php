<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SoftlogicGT\NeoPayDynamicRetail\Actions\Authorization;
use SoftlogicGT\NeoPayDynamicRetail\Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_sale_sends_formatted_body_and_headers(): void
    {
        $this->fake();

        Authorization::sale(230, 100.50, 10)->submit();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://auth.test/authenticate'
            && $r->header('membershipId') === ['MEM']
            && $r->header('secretKey') === ['SECRET']);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://pos.test/api/authorizationpaymentcommerce'
            && $r->header('Authorization') === ['Bearer JWT']
            && $r->header('merchantUser') === ['user']
            && $r->header('merchantPasswd') === ['pass']
            && $r['transactionType'] === 'SA'
            && $r['systemsTraceNo'] === '000230'
            && $r['amount']['amountTrans'] === '10050'
            && $r['amount']['additionalAmounts'] === '1000'
            && $r['merchant'] === ['terminalId' => 'T1', 'cardAcqId' => 'A1']);
    }

    public function test_sale_without_tip_leaves_additional_amounts_empty(): void
    {
        $this->fake();

        Authorization::sale(1, 10)->submit();

        $this->assertSame('', $this->posRequests()[0]['amount']['additionalAmounts']);
    }

    public function test_zero_tip_is_sent_as_000(): void
    {
        $this->fake();

        Authorization::sale(1, 10, 0)->submit();

        $this->assertSame('000', $this->posRequests()[0]['amount']['additionalAmounts']);
    }

    public function test_amounts_are_rounded_without_float_drift(): void
    {
        $this->fake();

        Authorization::sale(1, 19.99)->submit();
        Authorization::sale(2, 0.1 + 0.2)->submit();

        $this->assertSame('1999', $this->posRequests()[0]['amount']['amountTrans']);
        $this->assertSame('30', $this->posRequests()[1]['amount']['amountTrans']);
    }

    public function test_trace_number_rolls_over_format(): void
    {
        $this->fake();

        Authorization::annulment(999999)->submit();
        Authorization::annulment('1')->submit();

        $this->assertSame('999999', $this->posRequests()[0]['systemsTraceNo']);
        $this->assertSame('000001', $this->posRequests()[1]['systemsTraceNo']);
    }

    public function test_additional_data_formats(): void
    {
        $this->fake();

        Authorization::saleInstallments(1, 10, 3)->submit();
        Authorization::saleExtraFinancing(2, 10, 18)->submit();
        Authorization::saleDiscount(3, 200, 50)->submit();
        Authorization::salePoints(4, 100)->submit();

        $sent = array_map(fn (Request $r) => $r['additionalData'], $this->posRequests());
        $this->assertSame(['VC03', 'EF18', 'VD000000005000', 'LU'], $sent);
    }

    public function test_annulment_sends_only_trace(): void
    {
        $this->fake();

        Authorization::annulment(234)->submit();

        $request = $this->posRequests()[0];
        $this->assertSame('AN', $request['transactionType']);
        $this->assertSame('000234', $request['systemsTraceNo']);
        $this->assertSame('', $request['amount']['amountTrans']);
    }

    public function test_reverse_sends_trace_and_amount(): void
    {
        $this->fake();

        Authorization::reverse(233, 1000)->submit();

        $request = $this->posRequests()[0];
        $this->assertSame('RV', $request['transactionType']);
        $this->assertSame('000233', $request['systemsTraceNo']);
        $this->assertSame('100000', $request['amount']['amountTrans']);
    }

    public function test_check_in_and_surcharge_send_padded_folio(): void
    {
        $this->fake();

        Authorization::checkIn(235, 600, 20240522)->submit();
        Authorization::surcharge(236, 100, 20240522)->submit();

        [$checkIn, $surcharge] = $this->posRequests();
        $this->assertSame('CI', $checkIn['transactionType']);
        $this->assertSame('RC', $surcharge['transactionType']);
        $this->assertSame('000020240522', $checkIn['privateUse63']['lodgingFolioNumber14']);
        $this->assertSame('000020240522', $surcharge['privateUse63']['lodgingFolioNumber14']);
    }

    public function test_check_out_sends_folio_and_zero_decimals(): void
    {
        $this->fake();

        Authorization::checkOut(237, 700, 20240522)->submit();

        $request = $this->posRequests()[0];
        $this->assertSame('CO', $request['transactionType']);
        $this->assertSame('000020240522', $request['privateUse63']['lodgingFolioNumber14']);
        $this->assertSame('000', $request['privateUse63']['taxAmount1']);
        $this->assertSame('000', $request['amount']['additionalAmounts']);
    }

    public function test_check_out_with_tip_and_tourist_tax(): void
    {
        $this->fake();

        Authorization::checkOut(237, 700, 1, 15.5, 7)->submit();

        $request = $this->posRequests()[0];
        $this->assertSame('1550', $request['amount']['additionalAmounts']);
        $this->assertSame('700', $request['privateUse63']['taxAmount1']);
    }
}
