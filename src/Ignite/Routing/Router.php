<?php

namespace Ignite\Routing;

use Ignite\Config\Loader\LoaderInterface;
use Ignite\Config\ConfigCache;
use Ignite\Routing\Generator\UrlGeneratorInterface;
use Ignite\Routing\Matcher\UrlMatcherInterface;
use InvalidArgumentException;

/**
 * The Router class is an example of the integration of all pieces of the routing system for easier use.
 *
 * @author Matthias Claessen
 */

// TODO: Improve code quality and variable names

class Router implements RouterInterface
{
    protected $matcher;
    protected $generator;

    protected $defaults;

    protected $context;
    protected $loader;
    protected $collection;

    protected $resource;
    protected $options;

    public function __construct(LoaderInterface $loader, $resource, array $options = [], RequestContext $context = null, array $defaults = [])
    {
        $this->loader = $loader;
        $this->resource = $resource;
        $this->context = null === $context ? new RequestContext() : $context;
        $this->defaults = $defaults;
        $this->setOptions($options);
    }

    /**
     * Set options
     *
     * Available options:
     *
     * cache_directory: The cache directory (or null to disable caching)
     * debug: Whether to enable debugging or not (false by default)
     * resource_type: Type hint for the main resource (optional)
     *
     * @param array $options
     *
     * @return void
     *
     * @throws InvalidArgumentException The exception that will be thrown when an unsupported option is provided
     */
    public function setOptions(array $options): void
    {
        $this->options = array(
            'cache_directory' => null,
            'debug' => false,
            'generator_class' => 'Ignite\\Routing\\Generator\\UrlGenerator',
            'generator_base_class' => 'Ignite\\Routing\\Generator\\UrlGenerator',
            'generator_dumper_class' => '',
            'generator_cache_class' => '',
            'matcher_class' => 'Ignite\\Routing\\Matcher\\UrlMatcher',
            'matcher_base_class' => 'Ignite\\Routing\\Matcher\\UrlMatcher',
            'matcher_dumper_class' => '',
            'matcher_cache_class' => '',
            'resource_type' => null
        );

        $invalid = [];
        $isInvalid = false;

        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                $isInvalid = true;
                $invalid[] = $key;
            }
        }

        if ($isInvalid) {
            throw new InvalidArgumentException(sprintf('The Router does not support the following options: "%s".', implode('\', \'', $invalid)));
        }
    }

    public function setOption($key, $value): void
    {
        if (!array_key_exists($key, $this->options)) {
            throw new InvalidArgumentException(sprintf('The Router does not support the "%s" option.', $key));
        }

        $this->options[$key] = $value;
    }

    /**
     * Get an option value.
     *
     * @param string $key The key
     *
     * @return mixed The value
     *
     * @throws InvalidArgumentException The exception that will be thrown when the router does not support the option
     */
    public function getOption(string $key): mixed
    {
        if (!array_key_exists($key, $this->options)) {
            throw new InvalidArgumentException(sprintf('The Router does not support the "%s" option.', $key));
        }

        return $this->options[$key];
    }

    /**
     * Get the RouteCollection instance associated with this Router.
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function getRouteCollection(): RouteCollection
    {
        if ($this->collection === null) {
            $this->collection = $this->loader->load($this->resource, $this->options['resource_type']);
        }

        return $this->collection;
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;

        $this->getMatcher()->setContext($context);
        $this->getGenerator()->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function generate(string $name, array $parameters = [], bool $absolute = false): string
    {
        return $this->getGenerator()->generate($name, $parameters, $absolute);
    }

    public function match(string $url): array|false
    {
        return $this->getMatcher()->match($url);
    }

    public function getMatcher(): UrlMatcherInterface
    {
        if ($this->matcher !== null) {
            return $this->matcher;
        }

        if ($this->options['cache_directory'] === null || $this->options['matcher_cache_class']) {
            return $this->matcher = new $this->options['matcher_class']($this->getRouteCollection(), $this->context, $this->defaults);
        }

        $class = $this->options['matcher_cache_class'];
        $cache = new ConfigCache($this->options['cache_directory'] . '/' . $class . '.php', $this->options['debug']);

        if (!$cache->isFresh($class)) {
            $dumper = new $this->options['matcher_dumper_class']($this->getRouteCollection());

            $options = array(
                'class' => $class,
                'base_class' => $this->options['matcher_base_class'],
            );

            $cache->write($dumper->dump($options), $this->getRouteCollection()->getResources());
        }

        require_once $cache;

        return $this->matcher = new $class($this->context, $this->defaults);
    }

    /**
     * Get the UrlGenerator instance associated with this Router.
     *
     * @return UrlGeneratorInterface A UrlGeneratorInterface instance
     */
    public function getGenerator(): UrlGeneratorInterface
    {
        if ($this->generator !== null) {
            return $this->generator;
        }

        if ($this->options['cache_directory'] === null || $this->options['generator_cache_class'] === null) {
            return $this->generator = new $this->options['generator_class']($this->getRouteCollection(), $this->context, $this->defaults);
        }

        $class = $this->options['generator_cache_class'];
        $cache = new ConfigCache($this->options['cache_directory'] . '/' . $class . '.php', $this->options['debug']);

        if (!$cache->isFresh($class)) {
            $dumper = new $this->options['generator_dumper_class']($this->getRouteCollection());

            $options = array(
                'class' => $class,
                'base_class' => $this->options['generator_base_class'],
            );

            $cache->write($dumper->dump($options), $this->getRouteCollection()->getResources());
        }

        require_once $cache;

        return $this->generator = new $class($this->context, $this->defaults);
    }

}