<?php

namespace Ignite\Routing;

/**
 * RouteCompilerInterface is the interface that all RouteCompiler classes must implement.
 *
 * @author Matthias Claessen
 */
interface RouteCompilerInterface
{
    /**
     * Compile the current route instance.
     *
     * @param Route $route A Route instance
     *
     * @return CompiledRoute A CompiledRoute instance
     */
    public function compile(Route $route): CompiledRoute;
}