<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SoftlogicGT\NeoPayDynamicRetail\Actions\Authorization;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SettlementDetail;
use SoftlogicGT\NeoPayDynamicRetail\Exceptions\PosException;
use SoftlogicGT\NeoPayDynamicRetail\Tests\TestCase;

class PosClientTest extends TestCase
{
    public function test_token_is_cached_between_requests(): void
    {
        $this->fake();

        Authorization::annulment(1)->submit();
        SettlementDetail::forBatch(243)->submit();

        $auth = collect(Http::recorded())->filter(fn ($p) => str_contains($p[0]->url(), 'auth.test'));
        $this->assertCount(1, $auth);
        $this->assertSame('JWT', Cache::get('neopay-dynamic-retail.token'));
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

    public function test_response_code_without_description_uses_code_as_message(): void
    {
        Http::fake([
            'auth.test/*' => Http::response(['accessToken' => ['token' => 'JWT']]),
            'pos.test/*' => Http::response(['responseCode' => '05']),
        ]);

        $this->expectExceptionObject(new PosException('responseCode 05'));
        Authorization::sale(1, 10)->submit();
    }

    public function test_missing_response_code_is_an_error(): void
    {
        Http::fake([
            'auth.test/*' => Http::response(['accessToken' => ['token' => 'JWT']]),
            'pos.test/*' => Http::response([]),
        ]);

        $this->expectException(PosException::class);
        Authorization::sale(1, 10)->submit();
    }

    public function test_http_failure_is_wrapped(): void
    {
        Http::fake([
            'auth.test/*' => Http::response(['accessToken' => ['token' => 'JWT']]),
            'pos.test/*' => Http::response([], 500),
        ]);

        $this->expectException(PosException::class);
        Authorization::sale(1, 10)->submit();
    }

    public function test_missing_url_throws(): void
    {
        config(['neopay-dynamic-retail.url' => null]);
        $this->fake();

        $this->expectExceptionMessage('neopay-dynamic-retail.url is not set.');
        Authorization::annulment(1)->submit();
    }

    public function test_missing_auth_url_throws(): void
    {
        config(['neopay-dynamic-retail.auth_url' => null]);
        $this->fake();

        $this->expectExceptionMessage('neopay-dynamic-retail.auth_url is not set.');
        Authorization::annulment(1)->submit();
    }

    public function test_authentication_without_token_throws(): void
    {
        Http::fake(['auth.test/*' => Http::response(['accessToken' => []])]);

        $this->expectExceptionMessage('Authentication did not return a token.');
        Authorization::annulment(1)->submit();
    }

    public function test_authentication_failure_is_wrapped(): void
    {
        Http::fake(['auth.test/*' => Http::response([], 401)]);

        $this->expectException(PosException::class);
        Authorization::annulment(1)->submit();
    }

    public function test_expired_token_is_renewed_and_request_retried_once(): void
    {
        Http::fake([
            'auth.test/*' => Http::sequence()
                ->push(['accessToken' => ['token' => 'OLD']])
                ->push(['accessToken' => ['token' => 'NEW']]),
            'pos.test/*' => Http::sequence()
                ->push([], 401)
                ->push(['responseCode' => '00']),
        ]);

        $response = Authorization::annulment(1)->submit();

        $this->assertSame('00', $response->get('responseCode'));
        $this->assertSame(['OLD'], $this->posRequests()[0]->header('Authorization'));
        $this->assertSame(['NEW'], $this->posRequests()[1]->header('Authorization'));
    }

    public function test_persistent_401_does_not_loop(): void
    {
        Http::fake([
            'auth.test/*' => Http::response(['accessToken' => ['token' => 'JWT']]),
            'pos.test/*' => Http::response([], 401),
        ]);

        try {
            Authorization::annulment(1)->submit();
            $this->fail('Expected PosException');
        } catch (PosException) {
            $this->assertCount(2, $this->posRequests());
        }
    }
}
