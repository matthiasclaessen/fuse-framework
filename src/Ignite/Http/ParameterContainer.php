<?php

namespace Ignite\Http;

/**
 * ParameterContainer is a container for key/value pairs.
 *
 * @author Matthias Claessen
 */
class ParameterContainer
{
    protected array $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function replace(array $parameters = []): void
    {
        $this->parameters = $parameters;
    }

    public function get($path, $default = null, $deep = false)
    {
        if (!$deep || false === $position = strpos($path, '[')) {
            return array_key_exists($path, $this->parameters) ? $this->parameters[$path] : $default;
        }

        $root = substr($path, 0, $position);
        if (!array_key_exists($root, $this->parameters)) {
            return $default;
        }

        $value = $this->parameters[$root];
        $currentKey = null;

        for ($i = $position, $pathLength = strlen($path); $i < $pathLength; $i++) {
            $character = $path[$i];

            if ($character === '[') {
                if ($currentKey !== null) {
                    throw new \InvalidArgumentException(sprintf('Halformed path. Unexpected "[" at position %d.', $i));
                }

                $currentKey = '';
            } else if ($character === ']') {
                if ($currentKey === null) {
                    throw new \InvalidArgumentException(sprintf('Malformed path. Unexpected "]" at position %d.', $i));
                }

                if (!is_array($value) || !array_key_exists($currentKey, $value)) {
                    return $default;
                }

                $value = $value[$currentKey];
                $currentKey = null;
            } else {
                if ($currentKey === null) {
                    throw new \InvalidArgumentException(sprintf('Malformed path. Unexpected "%s" at position %d.', $character, $i));
                }

                $currentKey .= $character;
            }
        }

        if ($currentKey !== null) {
            throw new \InvalidArgumentException('Malformed path. Path must end with "]".');
        }

        return $value;
    }

    public function set($key, $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    public function remove($key): void
    {
        unset($this->parameters[$key]);
    }

    public function getAlphabetic($key, $default = '', $deep = false): string
    {
        return preg_replace('/[^[:alpha:]]/', '', $this->get($key, $default, $deep));
    }

    public function getAlphabeticAndDigits($key, $default = '', $deep = false): string
    {
        return preg_replace('/[^[:alnum:]]/', '', $this->get($key, $default, $deep));
    }

    public function getDigits($key, $default = '', $deep = false): string
    {
        return preg_replace('/[^[:digit:]]/', '', $this->get($key, $default, $deep));
    }

    public function getInt($key, $default = 0, $deep = false): int
    {
        return (int)$this->get($key, $default, $deep);
    }

}