<?php

namespace Ignite\Routing\Exception;

use Ignite\Container\Exception\ExceptionInterface;
use InvalidArgumentException;

/**
 * Exception thrown when a route does not exist.
 *
 * @author Matthias Claessen
 */

class RouteNotFoundException extends InvalidArgumentException implements ExceptionInterface
{

}