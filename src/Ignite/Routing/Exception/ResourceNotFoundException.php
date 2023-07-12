<?php

namespace Ignite\Routing\Exception;

use Ignite\Container\Exception\ExceptionInterface;
use RuntimeException;

/**
 * The resource was not found.
 *
 * This exception should trigger an HTTP 404 response in your application code.
 *
 * @author Matthias Claessen
 */
class ResourceNotFoundException extends RuntimeException implements ExceptionInterface
{

}