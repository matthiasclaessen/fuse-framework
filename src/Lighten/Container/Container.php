<?php

namespace Lighten\Container;

class Container
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected  static $instance;
    protected $bindings = [];
    protected $instances = [];

    public function bind($abstract, $concrete = null, $shared = false)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

}