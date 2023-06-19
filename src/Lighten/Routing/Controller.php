<?php

namespace lumen\framework\src\Lighten\Routing;

use lumen\framework\src\Lighten\Foundation\Application;

class Controller
{
    public string $action;

    public function render($view, $params = [])
    {
        return Application::$app->view->render($view, $params);
    }
}