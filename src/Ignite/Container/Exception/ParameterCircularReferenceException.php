<?php

namespace Ignite\Container\Exception;

class ParameterCircularReferenceException extends RuntimeException
{
    private array $parameters;

    public function __construct($parameters)
    {
        parent::__construct(sprintf('Circular reference detected for parameter "%s" ("%s" > "%s").', $parameters[0], implode('">"', $parameters), $parameters[0]));

        $this->parameters = $parameters;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}