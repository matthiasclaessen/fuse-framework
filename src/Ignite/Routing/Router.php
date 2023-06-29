<?php

namespace Ignite\Routing;

use Ignite\Config\Loader\LoaderInterface;
use Ignite\Config\ConfigCache;
use InvalidArgumentException;

/**
 * The Router class is an example of the integration of all pieces of the routing system for easier use.
 *
 * @author Matthias Claessen
 */
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
}