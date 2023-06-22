<?php

namespace Lighten\Routing;

class Route
{
    protected string $method;
    protected string $uri;
    protected string $action;

    public function __construct($method, $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    public function matches($method, $uri): bool
    {
        if ($this->method !== $method) {
            return false;
        }

        $pattern = '/^' . str_replace('/', '\/', $this->uri) . '$/';
        return (bool)preg_match($pattern, $uri);
    }

    public function execute()
    {
        [$controllerName, $methodName] = explode('@', $this->action);

        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (method_exists($controller, $methodName)) {
                return $controller->$methodName();
            }
        }

        throw new \Exception('Invalid route action');
    }

}