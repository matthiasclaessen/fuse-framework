<?php

namespace Ignite\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;

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
     * @var array
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected array $instances = [];

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

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    public function singleton($abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function make($abstract, array $parameters = [])
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {

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

    protected function isShared($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['shared'];
        }

        return false;
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

}