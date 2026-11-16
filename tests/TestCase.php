<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use SoftlogicGT\NeoPayDynamicRetail\Facades\NeoPayPos;
use SoftlogicGT\NeoPayDynamicRetail\NeoPayDynamicRetailServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [NeoPayDynamicRetailServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['NeoPayPos' => NeoPayPos::class];
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

    protected function fake(string $responseCode = '00', array $extra = []): void
    {
        Http::fake([
            'auth.test/*' => Http::response(['accessToken' => ['token' => 'JWT']]),
            'pos.test/*' => Http::response($extra + [
                'responseCode' => $responseCode,
                'privateUse63' => ['alternateHostResponse22' => 'FONDOS INSUFICIENTES'],
            ]),
        ]);
    }

    protected function posRequests(): array
    {
        return collect(Http::recorded())
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (Request $r) => str_starts_with($r->url(), 'https://pos.test/'))
            ->values()
            ->all();
    }
}
