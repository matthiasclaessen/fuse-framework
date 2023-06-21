<?php

namespace Lighten\Routing;

use Lighten\Foundation\Application;
use Lighten\Http\Request;
use Lighten\Http\Response;

/**
 * The Router class.
 */
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

    /**
     * Register a new GET route with the router.
     *
     * @param string $uri
     * @param array $action
     * @return void
     */
    public function get(string $uri, array $action): void
    {
        $this->routes['GET'][$uri] = $action;
    }

    /**
     * Register a new POST route with the router.
     *
     * @param string $uri
     * @param array $action
     * @return void
     */
    public function post(string $uri, array $action): void
    {
        $this->routes['POST'][$uri] = $action;
    }

    public function resolve(): string
    {
        $action = $this->routes[$this->request->method()][$this->request->path()] ?? false;

//        echo '<pre>';
//        var_dump($action);
//        echo '</pre>';
//        exit;

        if ($action === false) {
            $this->response->setStatusCode(404);
            return '404 - Not Found!';
        }

        if (is_string($action)) {
            return Application::$app->view->render($action);
        }

        if (is_array($action)) {
            return 'Action is an array!';
        }

        return call_user_func($action, $this->request, $this->response);
    }


}