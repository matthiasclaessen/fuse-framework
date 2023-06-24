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
        $this->registerBindings();
    }

    public function registerBindings()
    {
        $this->bind('app', $this);
        $this->singleton('router', function ($app) {
            return new Router($app);
        });
    }
    public function registerRouter()
    {
        $this->router = $this->make('router');
    }
}