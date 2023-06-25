<?php

namespace Ignite\Routing;

use Ignite\Container\Container;
use Ignite\Http\Request;

class Route
{
    public string $uri;
    public array $methods;
    public array $action;
    public mixed $controller;
    protected Router $router;
    protected Container $container;

    public function __construct($methods, $uri, $action)
    {
        $this->uri = $uri;
        $this->methods = (array)$methods;
        $this->action = $action;

        if (in_array('GET', $this->methods) && !in_array('HEAD', $this->methods)) {
            $this->methods[] = 'HEAD';
        }
    }

    /**
     * Run the route action and return the response
     *
     * @return mixed
     */
    public function run()
    {
        $this->container = $this->container ?: new Container();

//        try {
//            // TODO: Finish try logic here
//        } catch (\HttpResponseException $exception)
//        {
//
//        }
    }

    public function matches($method, $uri): bool
    {
        if ($this->method !== $method) {
            return false;
        }

        $pattern = '/^' . str_replace('/', '\/', $this->uri) . '$/';
        return (bool)preg_match($pattern, $uri);
    }

    public function getDomain(): array|string|null
    {
        return isset($this->action) ? str_replace(['http://', 'https://'], '', $this->action) : null;
    }

    public function uri(): string
    {
        return $this->uri;
    }


    public function setContainer(Container $container): Route
    {
        $this->container = $container;

        return $this;
    }

}