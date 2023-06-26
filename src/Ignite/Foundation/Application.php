<?php

namespace Ignite\Foundation;

use Ignite\Container\Container;

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

    protected string $databasePath;

    /**
     * Create a new Ignite application instance.
     *
     * @param string|null $basePath
     * @return void
     */
    public function __construct(string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

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

        $this->bindPathsInContainer();

        return $this;
    }

    protected function bindPathsInContainer(): void
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.database', $this->databasePath());

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
        $appPath = $this->appPath ?? $this->basePath . DIRECTORY_SEPARATOR . 'app';

        return $appPath . ($path != '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Set the application directory.
     *
     * @param string $path
     * @return $this
     */
    public function useAppPath($path): static
    {
        $this->appPath = $path;

        $this->instance('path', $path);

        return $this;
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

    /**
     * Get the path to the application configuration files.
     *
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config' . ($path != '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the database directory.
     *
     * @param string $path
     * @return string
     */
    public function databasePath(string $path = ''): string
    {
        return ($this->databasePath ?? $this->basePath . DIRECTORY_SEPARATOR . 'database') . ($path != '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function publicPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'public';
    }

    /**
     * Set the public / web directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function usePublicPath($path)
    {
        $this->publicPath = $path;

        $this->instance('path.public', $path);

        return $this;
    }

}