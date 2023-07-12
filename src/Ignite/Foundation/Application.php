<?php

namespace Ignite\Foundation;

use Ignite\Http\Request;
use Ignite\Routing\Router;

class Application
{
    /**
     * The Fuse framework version.
     *
     * @var string
     */
    const VERSION = '0.0.1';

    protected string $basePath;


    protected Router $router;


    public function __construct(string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }
    }

    public function setBasePath($basePath): Application
    {
        $this->basePath = rtrim($basePath, '\/');

        return $this;
    }

}