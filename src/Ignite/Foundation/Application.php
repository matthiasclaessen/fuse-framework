<?php

namespace Ignite\Foundation;

use Ignite\Container\Container;
use Ignite\Routing\Router;
use Ignite\View\View;

class Application extends Container
{
    /**
     * The Fuse framework version.
     *
     * @var string
     */
    const VERSION = '0.0.1';

    /**
     * The base path for the Fuse installation.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * The custom application path defined by the developer.
     *
     * @var string
     */
    protected string $appPath;


    /**
     * Create a new Ignite application instance.
     *
     * @param string|null $basePath
     * @return void
     */
    public function __construct(string $basePath = null)
    {
        $this->registerBaseBindings();
    }

    public function version(): string
    {
        return static::VERSION;
    }

    public function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);


    }

    public function setBasePath($basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        return $this;
    }

    protected function bindPathsInContainer(): void
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());

        $this->instance('path.bootstrap', $this->bootstrapPath());
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @param string $path
     * @return string
     */
    public function path(string $path = ''): string
    {
        $appPath = $this->appPath ?: $this->basePath . DIRECTORY_SEPARATOR . 'app';

        return $appPath . ($path != '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the base path of the Fuse installation.
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path != '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function bootstrapPath($path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . ($path != '' ? DIRECTORY_SEPARATOR . $path : '');
    }


}