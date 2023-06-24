<?php

namespace Lighten\Container;

use Closure;
use Exception;
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

    public function bind($abstract, $concrete = null, $shared = false): void
    {
        if ($concrete === null) {
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
    protected function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new \ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class ($concrete) is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $resolvedDependencies = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($resolvedDependencies);
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

    protected function isShared($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['shared'];
        }

        return false;
    }

}