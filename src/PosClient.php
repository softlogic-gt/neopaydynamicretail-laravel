<?php

namespace SoftlogicGT\NeoPayDynamicRetail;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SoftlogicGT\NeoPayDynamicRetail\Exceptions\PosException;

class PosClient
{
    private const TOKEN_KEY = 'neopay-dynamic-retail.token';

    private const TOKEN_TTL = 240;

    private PosLogger $logger;

    public function __construct(?PosLogger $logger = null)
    {
        $this->logger = $logger ?? new PosLogger;
    }

    public function post(string $endpoint, array $body): PosResponse
    {
        $body += [
            'merchant' => [
                'terminalId' => config('neopay-dynamic-retail.terminal_id'),
                'cardAcqId' => config('neopay-dynamic-retail.card_acq_id'),
            ],
        ];

        $response = $this->send($endpoint, $body);
        $code = $response['responseCode'] ?? null;

        if ($code !== '00') {
            $reason = $response['privateUse63']['alternateHostResponse22'] ?? "responseCode {$code}";
            throw new PosException($reason, $response);
        }

        return new PosResponse($response);
    }

    private function send(string $endpoint, array $body, bool $retry = true): array
    {
        $this->logger->request($endpoint, $body);

        try {
            $data = $this->http()->post($endpoint, $body)->throw()->json() ?? [];
            $this->logger->response($endpoint, $data);

            return $data;
        } catch (RequestException $e) {
            $this->logger->failure($endpoint, $e->response->status());

            if ($retry && $e->response->status() === 401) {
                Cache::forget(self::TOKEN_KEY);

                return $this->send($endpoint, $body, false);
            }

            throw new PosException($e->getMessage(), [], $e);
        }
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->required('url'))
            ->timeout(config('neopay-dynamic-retail.timeout'))
            ->acceptJson()
            ->withHeaders([
                'Authorization' => $this->token(),
                'merchantUser' => (string) config('neopay-dynamic-retail.merchant_user'),
                'merchantPasswd' => (string) config('neopay-dynamic-retail.merchant_password'),
            ]);
    }

    private function token(): string
    {
        return Cache::remember(self::TOKEN_KEY, self::TOKEN_TTL, function () {
            try {
                $token = Http::timeout(config('neopay-dynamic-retail.timeout'))
                    ->withHeaders([
                        'membershipId' => (string) config('neopay-dynamic-retail.membership_id'),
                        'secretKey' => (string) config('neopay-dynamic-retail.secret_key'),
                    ])
                    ->get($this->required('auth_url'))
                    ->throw()
                    ->json('accessToken.token');
            } catch (RequestException $e) {
                $this->logger->failure('authenticate', $e->response->status());

                throw new PosException($e->getMessage(), [], $e);
            }

            return $token ?: throw new PosException('Authentication did not return a token.');
        });
    }

    private function required(string $key): string
    {
        return config("neopay-dynamic-retail.{$key}")
            ?: throw new PosException("neopay-dynamic-retail.{$key} is not set.");
    }
}
