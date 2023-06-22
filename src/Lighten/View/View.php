<?php

namespace Lighten\View;

class View
{
    protected $viewPath;
    protected $data = [];

    public function __construct($viewPath)
    {
        $this->viewPath = $viewPath;
    }

    public function with($key, $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function render(): bool|string
    {
        extract($this->data);

        ob_start();
        include $this->getViewFullPath();
        return ob_get_clean();
    }

    protected function getViewFullPath(): string
    {
        $viewDirectory = 'views/';
        $viewExtensions = '.php';

        return $viewDirectory . $this->viewPath . $viewExtensions;
    }
}