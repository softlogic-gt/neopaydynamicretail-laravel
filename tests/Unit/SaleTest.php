<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use SoftlogicGT\NeoPayDynamicRetail\Actions\Sale;
use SoftlogicGT\NeoPayDynamicRetail\Exceptions\PosException;
use SoftlogicGT\NeoPayDynamicRetail\NeoPayDynamicRetailServiceProvider;

class SaleTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [NeoPayDynamicRetailServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('neopay-dynamic-retail.url', 'https://pos.test/');
        $app['config']->set('neopay-dynamic-retail.merchant_id', 'M1');
        $app['config']->set('neopay-dynamic-retail.terminal_id', 'T1');
    }

    public function test_sale_posts_body_with_identity(): void
    {
        Http::fake(['pos.test/*' => Http::response(['approved' => true])]);

        $response = Sale::make(['amount' => 10.5])->submit();

        $this->assertTrue($response['approved']);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://pos.test/sale'
            && $r['amount'] === 10.5
            && $r['terminalId'] === 'T1');
    }

    public function test_http_error_throws_pos_exception(): void
    {
        Http::fake(['pos.test/*' => Http::response([], 500)]);

        $this->expectException(PosException::class);
        Sale::make(['amount' => 1])->submit();
    }
}
