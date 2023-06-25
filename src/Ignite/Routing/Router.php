<?php

namespace Ignite\Routing;

use Exception;
use Ignite\Container\Container;

/**
 * The Router class.
 */
class Router
{
    protected array $routes;

    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected Container $container;

    public function __construct(Container $container = null)
    {
        $this->routes = [];
        $this->container = $container ?: new Container();
    }

    /**
     * Register a new GET route with the router.
     *
     * @param string $uri
     * @param array|callable|null|string $action
     * @return Route
     */
    public function get($uri, $action = null): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function addRoute($method, $uri, $action): void
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