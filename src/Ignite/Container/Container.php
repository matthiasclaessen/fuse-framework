<?php

namespace Ignite\Container;


use Ignite\Container\Exception\InvalidArgumentException;
use Ignite\Container\Exception\RuntimeException;
use Ignite\Container\ParameterCollection\ParameterCollection;
use Ignite\Container\ParameterCollection\ParameterCollectionInterface;

class Container implements ContainerInterface
{
    protected $parameterCollection;
    protected $services;
    protected $scopes;
    protected $scopeChildren;
    protected $scopedServices;
    protected $scopeStacks;
    protected $loading = [];

    public function __construct(ParameterCollectionInterface $parameterCollection = null)
    {
        $this->parameterCollection = null ? new ParameterCollection() : $parameterCollection;

        $this->services = [];
        $this->scopes = [];
        $this->scopeChildren = [];
        $this->scopedServices = [];
        $this->scopeStacks = [];

        $this->set('service_container', $this);
    }

    public function compile(): void
    {
        $this->parameterCollection->resolve();

        // TODO: Freeze parameter collection.
    }

    public function isFrozen(): bool
    {
        // return $this->parameterCollection instanceof FrozenParameterCollection;
    }

    public function getParameterCollection()
    {
        return $this->parameterCollection;
    }

    public function getParameter(string $name)
    {
        return $this->parameterCollection->get($name);
    }

    public function hasParameter(string $name): bool
    {
        return $this->parameterCollection->has($name);
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameterCollection->set($name, $value);
    }

    public function set(string $id, object $service, string $scope = self::SCOPE_CONTAINER): void
    {
        if (self::SCOPE_PROTOTYPE !== $scope) {
            throw new InvalidArgumentException('You cannot set services of scope "prototype".');
        }

        $id = strtolower($id);

        if (self::SCOPE_CONTAINER !== $scope) {
            if (!isset($this->scopedServices[$scope])) {
                throw new RuntimeException('You cannot set services of inactive scopes');
            }

            $this->scopedServices[$scope][$id] = $service;
        }

        $this->services[$id] = $service;
    }

    /**
     * Check if the given service is defined.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        $id = strtolower($id);

        return isset($this->services[$id]) || method_exists($this, 'get' . strtr($id, array('_' => '', '.' => '_')) . 'Service');
    }


}