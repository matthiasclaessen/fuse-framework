<?php

namespace Lighten\Routing;

class Route
{
    /**
     * The URI pattern the route responds to.
     *
     * @var string
     */
    public string $uri;

    /**
     * The HTTP methods the route responds to.
     *
     * GET, POST, PUT, PATCH, DELETE
     *
     * @var array
     */
    public array $methods;

    /**
     * The route action array.
     *
     * @var array
     */
    public array $action;

    /**
     * The router instance used by the route.
     *
     * @var Router
     */
    protected Router $router;


    public function __construct($methods, $uri, $action)
    {
        $this->uri = $uri;
        $this->methods = $methods;
        $this->action = $action;
    }
    
}