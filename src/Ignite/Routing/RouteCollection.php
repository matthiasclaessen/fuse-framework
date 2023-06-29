<?php

namespace Ignite\Routing;

use ArrayIterator;

// TODO: Add PHPDocs to methods

class RouteCollection
{
    private array $routes;
    private array $resources;
    private string $prefix;
    private $parent;

    public function __construct()
    {
        $this->routes = [];
        $this->resources = [];
        $this->prefix = '';
    }

    public function __clone()
    {
        foreach ($this->routes as $name => $route) {
            $this->routes[$name] = clone $route;
            if ($route instanceof RouteCollection) {
                $this->routes[$name]->setParent($this);
            }
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(RouteCollection $parent): void
    {
        $this->parent = $parent;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->routes);
    }

    public function add(string $name, Route $route): void
    {
        if (!preg_match('/^[a-z0-9A-Z_.]+$/', $name)) {
            throw new \InvalidArgumentException(sprintf('The provided route name "%s" contains non-valid characters. A route name must only contain digits (0-9), letters (a-z and A-Z), underscores(_) and dots(.).', $name));
        }

        $parent = $this;

        while ($parent->getParent()) {
            $parent = $parent->getParent();
        }

        if ($parent) {
            $parent->remove($name);
        }

        $this->routes[$name] = $route;
    }

    public function all(): array
    {
        $routes = [];

        foreach ($this->routes as $name => $route) {
            if ($route instanceof RouteCollection) {
                $routes = array_merge($routes, $route->all());
            } else {
                $routes[$name] = $route;
            }
        }

        return $routes;
    }

    public function get($name)
    {
        foreach (array_reverse($this->routes) as $routes) {
            if (!$routes instanceof RouteCollection) {
                continue;
            }

            if (null !== $route = $routes->get($name)) {
                return $route;
            }
        }

        if (isset($this->routes[$name])) {
            return $this->routes[$name];
        }
    }

    /**
     * Remove a route by name.
     *
     * @param string $name The route name
     */
    public function remove(string $name): void
    {
        if (isset($this->routes[$name])) {
            unset($this->routes[$name]);
        }

        foreach ($this->routes as $routes) {
            if ($routes instanceof RouteCollection) {
                $routes->remove($name);
            }
        }
    }

    public function addCollection(RouteCollection $collection, $prefix)
    {
        $collection->setParent($this);
        $collection->addPrefix($prefix);

        // Remove all routes with the same name in all existing collections
        foreach (array_keys($collection->all()) as $name) {
            $this->remove($name);
        }

        $this->routes[] = $collection;
    }

    public function addPrefix($prefix): void
    {
        // A prefix must not end with a slash
        $prefix = rtrim($prefix, '/');

        if (!$prefix) {
            return;
        }

        if (!strpos($prefix, '/', 0)) {
            $prefix = '/' . $prefix;
        }

        $this->prefix = $prefix . $this->prefix;

        foreach ($this->routes as $name => $route) {
            if ($route instanceof RouteCollection) {
                $route->addPrefix($prefix);
            } else {
                $route->setPattern($prefix . $route->getPattern());
            }
        }
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getResources()
    {
        $resources = $this->resources;
        foreach ($this as $routes) {
            if ($routes instanceof RouteCollection) {
                $resources = array_merge($resources, $routes->getResources());
            }
        }

        return array_unique($resources);
    }

    public function addResource(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }
}