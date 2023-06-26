<?php

namespace Ignite\Container;

use Closure;
use Exception;
use LogicException;
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

    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     * @return bool
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

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

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, Closure|string $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;
    }

    public function make($abstract, array $parameters = []): ?object
    {
        return $this->resolve($abstract, $parameters);
    }

    public function resolve($abstract, $parameters = [])
    {
        $abstract = $this->getAlias(($abstract));

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }


    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param callable|string $abstract
     * @return mixed
     */
    protected function getConcrete(callable|string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if the given concrete is buildable.
     *
     * @param mixed $concrete
     * @param string $abstract
     * @return bool
     */
    protected function isBuildable(mixed $concrete, string $abstract): bool
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

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract]) ? $this->getAlias($this->aliases[$abstract]) : $abstract;
    }

    public function flush(): void
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
    public function __set(string $key, mixed $value)
    {
        $this[$key] = $value;
    }

}