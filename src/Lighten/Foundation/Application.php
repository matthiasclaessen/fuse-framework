<?php

namespace Lighten\Foundation;

use Lighten\Routing\Router;
use Lighten\Http\Request;
use Lighten\Http\Response;
use Lighten\Routing\Controller;
use Lighten\View\View;

class Application
{
    public $router;

    public View $view;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function run(): void
    {
        try {
            $response = $this->router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
            echo $response;
        } catch (\Exception $exception) {
            echo '404 - Not Found!';
        }
    }
}