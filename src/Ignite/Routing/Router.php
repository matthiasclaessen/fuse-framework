<?php

namespace Ignite\Routing;

use Ignite\Http\Request;
use Ignite\Http\Response;

class Router
{
    public array $routes;
    public Request $request;
    public Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, mixed $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, mixed $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function resolve(): string
    {
        $action = $this->routes[$this->request->getMethod()][$this->request->getBasePath()] ?? false;

        if ($action === false) {
            return 'Route not found!';
        }

        if (is_string($action)) {
            return 'Route is not called through a controller!';
        }

        if (is_array($action)) {
            return 'Route is called through a controller!';
        }

        return call_user_func($action, $this->request, $this->response);
    }
}