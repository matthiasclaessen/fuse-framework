<?php

namespace Ignite\View;

class View
{
    protected $viewPath;
    protected $data = [];
    protected $layout;

    public function __construct($viewPath)
    {
        $this->viewPath = $viewPath;
    }

    public function with($key, $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function layout($layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function render(): bool|string
    {
        extract($this->data);

        if ($this->layout)
        {
            return $this->renderWithLayout();
        }

        ob_start();
        include $this->getViewFullPath();
        return ob_get_clean();
    }

    protected function getViewFullPath(): string
    {
        $viewDirectory = 'resources/views/';
        $viewExtensions = '.php';

        return $viewDirectory . $this->viewPath . $viewExtensions;
    }

    protected function renderWithLayout(): bool|string
    {
        $layoutDirectory = 'resources/views/layouts';
        $layoutExtension = '.php';
        $content = $this->renderViewContent();

        ob_start();
        include $layoutDirectory . $this->layout . $layoutExtension;
        return ob_get_clean();
    }

    protected function renderViewContent(): bool|string
    {
        ob_start();
        include $this->getViewFullPath();
        return ob_get_clean();
    }
}