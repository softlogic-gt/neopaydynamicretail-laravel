<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Actions;

use InvalidArgumentException;
use SoftlogicGT\NeoPayDynamicRetail\PosClient;
use SoftlogicGT\NeoPayDynamicRetail\PosResponse;

abstract class PosAction
{
    protected string $endpoint;

    public function __construct(protected array $body = [], protected ?PosClient $client = null)
    {
        $this->client ??= app(PosClient::class);
    }

    public function submit(): PosResponse
    {
        return $this->client->post($this->endpoint, $this->body);
    }

    protected static function amount(float $value): string
    {
        $cents = (int) round($value * 100);

        if ($cents <= 0 || strlen((string) $cents) > 12) {
            throw new InvalidArgumentException("Invalid amount: {$value}.");
        }

        return (string) $cents;
    }

    protected static function decimals(float $value): string
    {
        $cents = (int) round($value * 100);

        if ($cents < 0 || strlen((string) $cents) > 12) {
            throw new InvalidArgumentException("Invalid amount: {$value}.");
        }

        return sprintf('%03d', $cents);
    }

    protected static function trace(int|string $number): string
    {
        return self::digits($number, 6, 'trace number');
    }

    protected static function folio(int|string $number): string
    {
        return self::digits($number, 12, 'folio');
    }

    protected static function reference(int|string $number): string
    {
        return self::digits($number, 12, 'retrieval reference');
    }

    protected static function count(int $value, string $name): int
    {
        if ($value < 1 || $value > 99) {
            throw new InvalidArgumentException("Invalid {$name}: {$value}. Must be between 1 and 99.");
        }

        return $value;
    }

    private static function digits(int|string $number, int $length, string $name): string
    {
        $number = (string) $number;

        if (! preg_match('/^\d{1,'.$length.'}$/', $number)) {
            throw new InvalidArgumentException("Invalid {$name}: {$number}. Must be 1 to {$length} digits.");
        }

        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }
}
