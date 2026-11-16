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

    public static function make(array $body = []): static
    {
        return new static($body);
    }

    public function setBody(array $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function submit(): array
    {
        return $this->client->post($this->endpoint, $this->body);
    }
}
