<?php

namespace Ignite\Routing\Exception;

use Ignite\Container\Exception\ExceptionInterface;
use InvalidArgumentException;

/**
 * Exception thrown when a parameter is not valid.
 *
 * @author Matthias Claessen
 */

class InvalidParameterException extends InvalidArgumentException implements ExceptionInterface
{

}