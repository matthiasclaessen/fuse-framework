<?php

namespace Ignite\Routing;

use Ignite\Container\Container;
use Ignite\Http\Request;
use InvalidArgumentException;

class Route
{
    private string $pattern;
    private array $defaults;
    private array $requirements;
    private array $options;
    private $compiled;

    private static $compilers = [];

    /**
     * Constructor
     *
     * @param string $pattern The pattern to match
     * @param array $defaults An array of default parameter values
     * @param array $requirements An array of requirements for parameters (regular expressions)
     * @param array $options An array of options
     */
    public function __construct(string $pattern, array $defaults = [], array $requirements = [], array $options = [])
    {
        $this->setPattern($pattern);
        $this->setDefaults($defaults);
        $this->setRequirements($requirements);
        $this->setOptions($options);
    }

    public function __clone()
    {
        $this->compiled = null;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Set the pattern.
     *
     * @param string $pattern The pattern
     * @return Route The current Route instance
     */
    public function setPattern(string $pattern): static
    {
        $this->pattern = trim($pattern);

        // A route must start with a slash
        if (empty($this->pattern) || !strpos($this->pattern, '/', 0)) {
            $this->pattern = '/' . $this->pattern;
        }

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set the options.
     *
     * @param array $options The options
     * @return Route The current Route instance
     */
    public function setOptions(array $options): static
    {
        $this->options = array_merge(array(
            'compiler_class' => 'Ignite\\Routing\\RouteCompiler'
        ), $options);

        return $this;
    }

    public function setOption($name, $value): static
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function getOption($name)
    {
        return $this->options[$name] ?? null;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Set the defaults.
     *
     * @param array $defaults The defaults
     *
     * @return Route The current Route instance
     */
    public function setDefaults(array $defaults): static
    {
        $this->defaults = [];
        foreach ($defaults as $name => $default) {
            $this->defaults[(string)$name] = $default;
        }

        return $this;
    }

    public function getDefault($name)
    {
        return $this->defaults[$name] ?? null;
    }

    public function hasDefault($name): bool
    {
        return array_key_exists($name, $this->defaults);
    }

    /**
     * Set a default value.
     *
     * @param string $name A variable name
     * @param mixed $default The default value
     *
     * @return Route The current Route instance
     */
    public function setDefault(string $name, mixed $default): static
    {
        $this->defaults[$name] = $default;
        return $this;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Set the requirements.
     *
     * @param array $requirements The requirements
     *
     * @return Route The current Route instance
     */
    public function setRequirements(array $requirements): static
    {
        $this->requirements = [];

        foreach ($requirements as $key => $regularExpression) {
            $this->requirements[$key] = $this->sanitizeRequirement($key, $regularExpression);
        }

        return $this;
    }

    public function getRequirement($key)
    {
        return $this->requirements[$key] ?? null;
    }

    /**
     * Set a requirement for the given key.
     *
     * @param string $key The key
     * @param string $regularExpression The regular expression
     * @return Route The current Route instance
     */
    public function setRequirement(string $key, string $regularExpression)
    {
        $this->requirements[$key] = $this->sanitizeRequirement($key, $regularExpression);

        return $this;
    }

    /**
     * Compile the route.
     *
     * @return CompiledRoute A CompiledRoute instance
     */
    public function compile(): CompiledRoute
    {
        if ($this->compiled !== null) {
            return $this->compiled;
        }

        $class = $this->getOption('compiler_status');

        if (!isset(self::$compilers[$class])) {
            self::$compilers[$class] = new $class;
        }

        return $this->compiled = self::$compilers[$class]->compile($this);
    }

    private function sanitizeRequirement($key, $regularExpression)
    {
        if (is_array($regularExpression)) {
            throw new InvalidArgumentException(sprintf('Routing requirements must be a string, array given for "%s"', $key));
        }

        if ('^' == $regularExpression[0]) {
            $regularExpression = substr($regularExpression, 1);
        }

        if (str_ends_with($regularExpression, '$')) {
            $regularExpression = substr($regularExpression, 0, -1);
        }

        return $regularExpression;
    }

}