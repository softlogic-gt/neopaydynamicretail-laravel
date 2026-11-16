<?php

namespace SoftlogicGT\NeoPayDynamicRetail;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use SoftlogicGT\NeoPayDynamicRetail\Exceptions\PosException;

class PosClient
{
    public function post(string $endpoint, array $body): array
    {
        try {
            return $this->http()->post($endpoint, $body + $this->identity())->throw()->json() ?? [];
        } catch (RequestException $e) {
            throw new PosException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function http(): PendingRequest
    {
        $url = config('neopay-dynamic-retail.url');

        if (!$url) {
            throw new PosException('NEOPAY_POS_URL is not set.');
        }

        return Http::baseUrl($url)
            ->timeout(config('neopay-dynamic-retail.timeout'))
            ->withBasicAuth((string) config('neopay-dynamic-retail.user'), (string) config('neopay-dynamic-retail.password'))
            ->acceptJson();
    }

    private function identity(): array
    {
        return [
            'merchantId' => config('neopay-dynamic-retail.merchant_id'),
            'terminalId' => config('neopay-dynamic-retail.terminal_id'),
        ];
    }
}
