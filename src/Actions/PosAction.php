<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

use SoftlogicGT\NeoPayDynamicRetail\PosClient;

abstract class PosAction
{
    protected string $endpoint;

    public function __construct(protected array $body = [], protected ?PosClient $client = null)
    {
        $this->client ??= new PosClient();
    }

    public function submit(): array
    {
        return $this->client->post($this->endpoint, $this->body);
    }

    protected static function amount(float $value): string
    {
        return (string) (int) round($value * 100);
    }

    protected static function decimals(float $value): string
    {
        return sprintf('%03d', (int) round($value * 100));
    }

    protected static function trace(int|string $number): string
    {
        return str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    protected static function folio(int|string $number): string
    {
        return str_pad((string) $number, 12, '0', STR_PAD_LEFT);
    }
}
