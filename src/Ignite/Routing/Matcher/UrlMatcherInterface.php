<?php

namespace Ignite\Routing\Matcher;

use Ignite\Routing\RequestContextAwareInterface;

/**
 * UrlMatcherInterface is the interface that all URL matcher classes must implement
 *
 * @author Matthias Claessen
 */
interface UrlMatcherInterface extends RequestContextAwareInterface
{
    /**
     * Try to match a URL with a set of routes.
     *
     * @param string $pathInfo The path info to be parsed
     * @return array An array of parameters
     *
     * @throws
     * @throws
     */
    public function match(string $pathInfo);
}