<?php

namespace Lighten\Foundation;

use Lighten\Container\Container;
use Lighten\Routing\Router;
use Lighten\View\View;

class Application extends Container
{
    public Router $router;

    public View $view;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function registerBindings()
    {
        
    }

    public function instance($abstract, $instance)
    {

    }

//    public function run(): void
//    {
//        try {
//            $response = $this->router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
//            echo $response;
//        } catch (\Exception $exception) {
//            echo '404 - Not Found!';
//        }
//    }
}