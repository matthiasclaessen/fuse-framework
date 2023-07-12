<?php

namespace Ignite\Routing\Exception;

use Exception;
use Ignite\Container\Exception\ExceptionInterface;
use RuntimeException;

class MethodNotAllowedException extends RuntimeException implements ExceptionInterface
{
    protected array $allowedMethods;

    public function __construct(array $allowedMethods, string $message = null, int $code = 0, Exception $previous = null)
    {
        $this->allowedMethods = array_map('strtoupper', $allowedMethods);

        parent::__construct($message, $code, $previous);
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}