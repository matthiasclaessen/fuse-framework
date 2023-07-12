<?php

namespace Ignite\Routing\Exception;

use Ignite\Container\Exception\ExceptionInterface;
use InvalidArgumentException;

/**
 * Exception thrown when a route cannot be generated because of missing mandatory parameters.
 *
 * @author Matthias Claessen
 */

class MissingMandatoryParametersException extends InvalidArgumentException implements ExceptionInterface
{

}