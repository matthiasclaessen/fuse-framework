<?php

namespace Ignite\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use TypeError;

class Container
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static Container $instance;

    /**
     * The container's bindings.
     *
     * @var array[]
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     *
     * @var object[]
     */
    protected array $instances = [];

    /**
     * The registered type aliases.
     *
     * @var string[]
     */
    protected array $aliases = [];

    /**
     * The stack of concretions currently being built.
     *
     * @var array[]
     */
    protected array $buildStack = [];

    public function bind($abstract, $concrete = null, $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new TypeError(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }


        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    public function getClosure($abstract, $concrete): Closure
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    public function singleton($abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function make($abstract, array $parameters = []): ?object
    {
        return $this->resolve($abstract, $parameters);
    }

    public function resolve($abstract, $parameters = [])
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }


    }

    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function isBuildable($concrete, $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    protected function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $exception) {
            throw new Exception("Target class [$concrete] does not exist.", 0, $exception);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable.", 0);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (Exception $exception) {
            array_pop($this->buildStack);

            throw $exception;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $resolved = [];

        foreach ($dependencies as $dependency) {
            if (isset($parameters[$dependency->name])) {
                $resolved[] = $parameters[$dependency->name];
            } elseif ($dependency->getClass) {
                $resolved[] = $this->make($dependency->getClass()->name);
            } elseif ($dependency->isDefaultValueAvailable()) {
                $resolved[] = $dependency->getDefaultValue();
            } else {
                throw new Exception("Unable to resolve dependency '{$dependency->name}'");
            }
        }

        return $resolved;
    }

    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Get the container's bindings.
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    protected function isShared($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['shared'];
        }

        return false;
    }

    public function flush()
    {
        $this->aliases = [];
        $this->bindings = [];
        $this->instances = [];
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param $container
     * @return mixed|null
     */
    public static function setInstance($container = null): mixed
    {
        return static::$instance = $container;
    }

    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

}