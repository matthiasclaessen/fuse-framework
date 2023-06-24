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
        if (is_callable($this->action)) {
            return call_user_func($this->action);
        }

        list($controller, $method) = explode('@', $this->action);
        if (class_exists("app/Http/Controllers/" . $controller . ".php")) {
            $controllerInstance = new $controller();
            return $controllerInstance->$method();
        }

        return "Can't find controller $controller";
    }

}