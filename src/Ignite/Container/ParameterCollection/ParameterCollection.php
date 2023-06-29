<?php

namespace Ignite\Container\ParameterCollection;

use Ignite\Container\Exception\ParameterCircularReferenceException;
use Ignite\Container\Exception\ParameterNotFoundException;
use Ignite\Container\Exception\RuntimeException;

class ParameterCollection implements ParameterCollectionInterface
{
    protected array $parameters = [];

    protected bool $resolved = false;

    public function __construct(array $parameters = [])
    {
        $this->add($parameters);
    }

    public function clear(): void
    {
        $this->parameters = [];
    }

    public function add(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get the service container parameters.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Get a service container parameter.
     *
     * @param string $name The parameter name
     * @return mixed The parameter value
     * @throws ParameterNotFoundException
     */
    public function get(string $name): mixed
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new ParameterNotFoundException($name);
        }

        return $this->parameters[$name];
    }

    /**
     * Remove a service container parameter.
     *
     * @param string $name
     * @return void
     */
    public function remove(string $name): void
    {
        unset($this->parameters[$name]);
    }

    /**
     * Set a service container parameter.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set(string $name, mixed $value): void
    {
        $this->parameters[strtolower($name)] = $value;
    }

    /**
     * Check if a parameter name is defined.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function resolve(): void
    {
        if ($this->resolved) {
            return;
        }

        $parameters = [];

        foreach ($this->parameters as $key => $value) {
            try {
                $value = $this->resolveValue($value);
                $parameters[$key] = $this->unescapeValue($value);
            } catch (ParameterNotFoundException $exception) {
                $exception->setSourceKey($key);

                throw $exception;
            }
        }

        $this->parameters = $parameters;
        $this->resolved = true;
    }

    /**
     * Replace parameter placeholders (%name%) by their values.
     *
     * @param mixed $value A value
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     * @return mixed The resolved value
     *
     * @throws ParameterNotFoundException if a placeholder references a parameter that does not exist
     * @throws ParameterCircularReferenceException if a circular reference is detected
     * @throws RuntimeException when a given parameter has a type problem
     */
    public function resolveValue($value, array $resolving = []): mixed
    {
        if (is_array($value)) {
            $args = [];

            foreach ($value as $key => $v) {
                $args[$this->resolveValue($key, $resolving)] = $this->resolveValue($v, $resolving);
            }

            return $args;
        }

        if (!is_string($value)) {
            return $value;
        }

        return $this->resolveString($value, $resolving);
    }

    public function resolveString($value, array $resolving = [])
    {
        if (preg_match('/^%([^%\s]+)%$/', $value, $match)) {
            $key = $match[1];

            if (isset($resolving[$key])) {
                return "Still resolving string!";
            }

            $resolving[$key] = true;

            return $this->resolved ? $this->get($key) : $this->resolveValue($this->get($key), $resolving);
        }

        return preg_replace_callback('/%%|%([^%\s]+)%/', function ($match) use ($resolving, $value) {
            if (!isset($match[1])) {
                return '%%';
            }

            $key = $match[1];

            if (isset($resolving[$key])) {
                return 'A string value must be composed of strings and/or numbers, but found parameters "%s" of type %s inside string value "%s".';
            }

            $resolved = $this->get($key);

            if (!is_string($resolved) && !is_numeric($resolved)) {
                return 'A string value must be composed of strings and/or numbers, but found parameters "%s" of type %s inside string value "%s".';
            }

            $resolved = (string)$resolved;
            $resolving[$key] = true;

            return $this->isResolved ? $resolved : $this->resolveString($resolved, $resolving);

        }, $value);
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function escapeValue($value): array|string
    {
        if (is_string($value)) {
            return str_replace('%', '%%', $value);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $v) {
                $result[$key] = $this->escapeValue($v);
            }

            return $result;
        }

        return $value;
    }

    public function unescapeValue($value): array|string
    {
        if (is_string($value)) {
            return str_replace('%%', '%', $value);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $v) {
                $result[$key] = $this->unescapeValue($v);
            }

            return $result;
        }

        return $value;
    }
}