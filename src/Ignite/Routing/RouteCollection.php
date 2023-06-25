<?php

namespace Ignite\Routing;

class RouteCollection
{
    protected $routes = [];

    protected $allRoutes = [];

    protected $nameList = [];

    protected $actionList = [];

    public function add(Route $route)
    {


        return $route;
    }

    protected function addToCollections($route)
    {
        $domainAndUri = $route->domain() . $route->getUri();
    }
}