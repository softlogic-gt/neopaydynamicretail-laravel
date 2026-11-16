<?php

namespace SoftlogicGT\NeoPayDynamicRetail\Exceptions;

use RuntimeException;
use Throwable;

class PosException extends RuntimeException
{
    public function __construct(string $message, public readonly array $response = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
