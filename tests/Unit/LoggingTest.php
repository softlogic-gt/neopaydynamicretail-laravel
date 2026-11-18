<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SoftlogicGT\NeoPayDynamicRetail\Actions\Authorization;
use SoftlogicGT\NeoPayDynamicRetail\Actions\SendDigitalVoucher;
use SoftlogicGT\NeoPayDynamicRetail\Exceptions\PosException;
use SoftlogicGT\NeoPayDynamicRetail\Tests\TestCase;

class LoggingTest extends TestCase
{
    private array $logged = [];

    private function captureLogs(): void
    {
        config(['neopay-dynamic-retail.log_channel' => 'pos']);
        Log::shouldReceive('channel')->with('pos')->andReturnSelf();
        foreach (['info', 'error'] as $level) {
            Log::shouldReceive($level)->andReturnUsing(function (string $message, array $context) use ($level) {
                $this->logged[] = [$level, $message, $context];
            });
        }
    }

    public function test_nothing_is_logged_by_default(): void
    {
        $this->fake();
        Log::shouldReceive('channel')->never();

        Authorization::sale(1, 10)->submit();

        $this->assertTrue(true);
    }

    public function test_request_and_response_are_logged_with_sensitive_data_masked(): void
    {
        $this->fake('00', [
            'cardHolderName' => 'JUAN PEREZ',
            'panPci' => '************1234',
            'voucher' => ['commerceVoucher' => 'COMERCIO TEXTO', 'clientVoucher' => 'CLIENTE TEXTO'],
            'report' => ['reportDetail' => [['card' => ['primaryAcctNum' => '4111111111115678']]]],
        ]);
        $this->captureLogs();

        Authorization::sale(230, 100)->submit();
        SendDigitalVoucher::make('500815000512', 'Email', 'secreto@correo.com', 'Juan Perez')->submit();

        $this->assertSame(['info', 'neopay-dynamic-retail request'], array_slice($this->logged[0], 0, 2));
        $this->assertSame('authorizationpaymentcommerce', $this->logged[0][2]['endpoint']);
        $this->assertSame('10000', $this->logged[0][2]['body']['amount']['amountTrans']);
        $this->assertSame('neopay-dynamic-retail response', $this->logged[1][1]);

        $response = $this->logged[1][2]['body'];
        $this->assertSame('***', $response['cardHolderName']);
        $this->assertSame('***1234', $response['panPci']);
        $this->assertSame('***', $response['voucher']['commerceVoucher']);
        $this->assertSame('***', $response['voucher']['clientVoucher']);
        $this->assertSame('***5678', $response['report']['reportDetail'][0]['card']['primaryAcctNum']);

        $notification = $this->logged[2][2]['body'];
        $this->assertSame('***', $notification['clientName']);
        $this->assertSame('***', $notification['notification']['to']);
    }

    public function test_credentials_and_token_never_reach_the_log(): void
    {
        $this->fake();
        $this->captureLogs();

        Authorization::sale(1, 10)->submit();

        $dump = json_encode($this->logged);
        foreach (['JWT', 'SECRET', 'MEM', 'pass', 'user'] as $secret) {
            $this->assertStringNotContainsString("\"{$secret}\"", $dump);
        }
    }

    public function test_http_failures_are_logged_without_response_body(): void
    {
        Http::fake([
            'auth.test/*' => Http::response(['accessToken' => ['token' => 'JWT']]),
            'pos.test/*' => Http::response(['detail' => 'datos sensibles'], 500),
        ]);
        $this->captureLogs();

        try {
            Authorization::sale(1, 10)->submit();
        } catch (PosException) {
        }

        $failure = collect($this->logged)->first(fn ($l) => $l[1] === 'neopay-dynamic-retail failure');
        $this->assertSame('error', $failure[0]);
        $this->assertSame(['endpoint' => 'authorizationpaymentcommerce', 'status' => 500], $failure[2]);
    }

    public function test_authentication_failure_is_logged(): void
    {
        Http::fake(['auth.test/*' => Http::response([], 401)]);
        $this->captureLogs();

        try {
            Authorization::sale(1, 10)->submit();
        } catch (PosException) {
        }

        $this->assertSame(['endpoint' => 'authenticate', 'status' => 401], $this->logged[1][2]);
    }
}
