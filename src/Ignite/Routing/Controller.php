<?php

namespace Ignite\Routing;

use Ignite\View\View;

abstract class Controller
{
    protected function view($viewPath, $data = [], $layout = null): bool|string
    {
        $view = new View($viewPath);
        $view->with($data);

        if ($layout) {
            $view->layout($layout);
        }

        return $view->render();
    }

}