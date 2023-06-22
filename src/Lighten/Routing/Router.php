<?php

namespace Lighten\Routing;

use Exception;

/**
 * The Router class.
 */
class Router
{
    protected array $routes;

    public function get($path, $action): void
    {
        $this->createRoute('GET', $path, $action);
    }

    public function createRoute($method, $uri, $action): void
    {
        $route = new Route($method, $uri, $action);
        $this->routes[] = $route;
    }

    public function dispatch($method, $uri)
    {
        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri)) {
                return $route->execute();
            }
        }

        throw new Exception('Route not found');
    }

}