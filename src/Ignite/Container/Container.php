<?php

namespace Ignite\Container;

use Closure;

/**
 * The Container class.
 *
 * @author Matthias Claessen
 */
class Container
{
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
     * The registered type aliases.
     *
     * @var array
     */
    protected array $aliases = [];

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this[$abstract]) or isset($this->instances[$abstract]);
    }

    /**
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, Closure|string $concrete = null, bool $shared = false): void
    {

    }


    /**
     * Alias a type to a shorter name.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Extract the type and alias from a given definition.
     *
     * @param array $definition
     * @return array
     */
    protected function extractAlias(array $definition): array
    {
        return array(key($definition), current($definition));
    }

    public function make(string $abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        return $object;

    }


    /*
    |--------------------------------------------------------------
    | Protected Functions
    |--------------------------------------------------------------
    */

    /**
     * Get the concrete type for a given abstract.
     *
     * @param string $abstract
     * @return mixed $concrete
     */
    protected function getConcrete(string $abstract): mixed
    {
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        } else {
            return $this->bindings[$abstract]['concrete'];
        }
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     * @return string
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
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
        return $concrete === $abstract or $concrete instanceof Closure;
    }

    /**
     * Determine if a given type is shared.
     *
     * @param string $abstract
     * @return bool
     */
    protected function isShared(string $abstract): bool
    {
        $set = isset($this->bindings[$abstract]['shared']);

        return $set and $this->bindings[$abstract]['shared'] === true;
    }


    /**
     * Determine if a given key exists.
     *
     * @param string $key
     * @return bool
     */
    public function keyExists(string $key): bool
    {
        return isset($this->bindings[$key]);
    }

    public function getKey(string $key)
    {
        // TODO: Implement offsetGet() method.
    }

    public function setKey(string $key, mixed $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function unsetKey(string $key): void
    {
        unset($this->bindings[$key]);

        unset($this->instances[$key]);
    }
}
