<?php

namespace Ignite\Container\ParameterCollection;

use Ignite\Container\Exception\ParameterNotFoundException;

/**
 * ParameterCollectionInterface
 *
 * @author Matthias Claessen
 */
interface ParameterCollectionInterface
{
    /**
     * Clear all parameters.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Add parameters to the service container parameters.
     *
     * @param array $parameters An array of parameters
     * @return void
     */
    public function add(array $parameters): void;

    /**
     * Get the service container parameters
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get a service container parameter.
     *
     * @param string $name The parameter name
     * @return mixed The parameter value
     *
     * @throws ParameterNotFoundException
     */
    public function get(string $name): mixed;

    /**
     * Set a service container parameter.
     *
     * @param string $name The parameter name
     * @param mixed $value The parameter value
     * @return void
     */
    public function set(string $name, mixed $value): void;

    /**
     * Check if a parameter name is defined.
     *
     * @param string $name The parameter name
     * @return bool
     */
    public function has(string $name): bool;

    public function resolve();

    public function resolveValue($value);
}