<?php

namespace Ignite\Container;

class Scope implements ScopeInterface
{
    private $name;
    private $parentName;

    public function __construct($name, $parentName = ContainerInterface::SCOPE_CONTAINER)
    {
        $this->name = $name;
        $this->parentName = $parentName;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParentName()
    {
        return $this->getParentName();
    }
}