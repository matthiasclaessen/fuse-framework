<?php

namespace Lighten\Routing;

use Lighten\Foundation\Application;

class Controller
{
    public string $action;

    public function render($view, $params = [])
    {
        return Application::$app->view->render($view, $params);
    }
}