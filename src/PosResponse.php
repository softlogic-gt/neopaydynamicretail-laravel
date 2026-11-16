<?php

namespace SoftlogicGT\NeoPayDynamicRetail;

class PosResponse
{
    public function __construct(private readonly array $data) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function authorization(): ?string
    {
        return $this->get('authIdResponse');
    }

    public function retrievalRefNo(): ?string
    {
        return $this->get('retrievalRefNo');
    }

    public function traceNumber(): ?string
    {
        return $this->get('systemsTraceNo');
    }

    public function message(): ?string
    {
        return $this->get('privateUse63.alternateHostResponse22');
    }

    public function commerceVoucher(): ?string
    {
        return $this->get('voucher.commerceVoucher');
    }

    public function clientVoucher(): ?string
    {
        return $this->get('voucher.clientVoucher');
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
