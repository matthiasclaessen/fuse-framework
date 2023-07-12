<?php

namespace Ignite\Routing\Generator;

use Ignite\Routing\RequestContextAwareInterface;

/**
 * UrlGeneratorInterface is the interface that all URL generator classes must implement.
 *
 * @author Matthias Claessen
 */
interface UrlGeneratorInterface extends RequestContextAwareInterface
{
    /**
     * Generate a URL from the given parameters.
     *
     * @param string $name The name of the route
     * @param mixed $parameters An array of parameters
     * @param bool $absolute Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generate(string $name, mixed $parameters = [], bool $absolute = false): string;
}