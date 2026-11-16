<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use SoftlogicGT\NeoPayDynamicRetail\Actions\Authorization;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SettlementDetail;
use SoftlogicGT\NeoPayDynamicRetail\Exceptions\PosException;
use SoftlogicGT\NeoPayDynamicRetail\NeoPayDynamicRetailServiceProvider;

class AuthorizationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [NeoPayDynamicRetailServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('neopay-dynamic-retail', [
            'url' => 'https://pos.test/api/',
            'auth_url' => 'https://auth.test/authenticate',
            'membership_id' => 'MEM',
            'secret_key' => 'SECRET',
            'merchant_user' => 'user',
            'merchant_password' => 'pass',
            'terminal_id' => 'T1',
            'card_acq_id' => 'A1',
            'timeout' => 5,
        ]);
        $app['config']->set('cache.default', 'array');
    }

    private function fake(string $responseCode = '00'): void
    {
        Http::fake([
            'auth.test/*' => Http::response(['accessToken' => ['token' => 'JWT']]),
            'pos.test/*' => Http::response([
                'responseCode' => $responseCode,
                'privateUse63' => ['alternateHostResponse22' => 'FONDOS INSUFICIENTES'],
            ]),
        ]);
    }

    public function test_sale_sends_formatted_body_and_headers(): void
    {
        $this->fake();

        Authorization::sale(230, 100.50, 10)->submit();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://auth.test/authenticate'
            && $r->header('membershipId') === ['MEM']
            && $r->header('secretKey') === ['SECRET']);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://pos.test/api/paymentcommerceauthorization'
            && $r->header('Authorization') === ['JWT']
            && $r->header('merchantUser') === ['user']
            && $r->header('merchantPasswd') === ['pass']
            && $r['transactionType'] === 'SA'
            && $r['systemsTraceNo'] === '000230'
            && $r['amount']['amountTrans'] === '10050'
            && $r['amount']['additionalAmounts'] === '1000'
            && $r['merchant'] === ['terminalId' => 'T1', 'cardAcqId' => 'A1']);
    }

    public function test_additional_data_formats(): void
    {
        $this->fake();

        Authorization::saleInstallments(1, 10, 3)->submit();
        Authorization::saleExtraFinancing(2, 10, 18)->submit();
        Authorization::saleDiscount(3, 200, 50)->submit();
        Authorization::salePoints(4, 100)->submit();

        $sent = collect(Http::recorded())->map(fn ($p) => $p[0]['additionalData'] ?? null)->filter()->values()->all();
        $this->assertSame(['VC03', 'EF18', 'VD000000005000', 'LU'], $sent);
    }

    public function test_check_out_sends_folio_and_zero_decimals(): void
    {
        $this->fake();

        Authorization::checkOut(237, 700, 20240522)->submit();

        Http::assertSent(fn (Request $r) => ($r['transactionType'] ?? null) === 'CO'
            && $r['privateUse63']['lodgingFolioNumber14'] === '000020240522'
            && $r['privateUse63']['taxAmount1'] === '000'
            && $r['amount']['additionalAmounts'] === '000');
    }

    public function test_token_is_cached_between_requests(): void
    {
        $this->fake();

        Authorization::annulment(1)->submit();
        SettlementDetail::forBatch(243)->submit();

        $auth = collect(Http::recorded())->filter(fn ($p) => str_contains($p[0]->url(), 'auth.test'));
        $this->assertCount(1, $auth);
    }

    public function test_non_zero_response_code_throws_with_reason(): void
    {
        $this->fake('51');

        try {
            Authorization::sale(1, 10)->submit();
            $this->fail('Expected PosException');
        } catch (PosException $e) {
            $this->assertSame('FONDOS INSUFICIENTES', $e->getMessage());
            $this->assertSame('51', $e->response['responseCode']);
        }
    }

    public function test_missing_url_throws(): void
    {
        config(['neopay-dynamic-retail.url' => null]);
        $this->fake();

        $this->expectException(PosException::class);
        Authorization::annulment(1)->submit();
    }
}
