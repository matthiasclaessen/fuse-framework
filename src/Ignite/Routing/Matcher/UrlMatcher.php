<?php

namespace Ignite\Routing\Matcher;

use Ignite\Routing\Exception\MethodNotAllowedException;
use Ignite\Routing\Exception\ResourceNotFoundException;
use Ignite\Routing\RequestContext;
use Ignite\Routing\RouteCollection;

/**
 * UrlMatcher matches URL based on a set of routes.
 *
 * @author Matthias Claessen
 */
class UrlMatcher implements UrlMatcherInterface
{
    protected RequestContext $context;
    protected $allow;

    private RouteCollection $routes;

    public function __construct(RouteCollection $routes, RequestContext $context)
    {
        $this->routes = $routes;
        $this->context = $context;
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    /**
     * Try to match a URL with a set of routes.
     *
     * @param string $pathInfo The path info to be parsed
     *
     * @return array An array of parameters
     *
     * @throws MethodNotAllowedException The exception that will be thrown when the resource was found but the request method is not allowed
     * @throws ResourceNotFoundException The exception that will be thrown when the resource could not be found
     */
    public function match(string $pathInfo): array
    {
        $this->allow = [];

        $result = $this->matchCollection($pathInfo, $this->routes);

        if ($result) {
            return $result;
        }

        throw 0 < count($this->allow)
            ? new MethodNotAllowedException(array_unique(array_map('strtoupper', $this->allow)))
            : new ResourceNotFoundException();
    }

    protected function matchCollection(string $pathInfo, RouteCollection $routes): array
    {
        $pathInfo = urldecode($pathInfo);

        foreach ($routes as $name => $route) {
            if ($route instanceof RouteCollection) {
                if (!str_contains($route->getPrefix(), '{') && !str_starts_with($pathInfo, $route->getPrefix())) {
                    continue;
                }

                $result = $this->matchCollection($pathInfo, $route);

                if (!$result) {
                    continue;
                }

                return $result;
            }

            $compiledRoute = $route->compile();

            // Check the static prefix of the URL first. Only use the more expensive preg_match function when it matches
            if ($compiledRoute->getStaticPrefix() !== '' && strpos($pathInfo, $compiledRoute->getStaticPrefix() !== 0)) {
                continue;
            }

            if (!preg_match($compiledRoute->getRegularExpression(), $pathInfo, $matches)) {
                continue;
            }

            // Check HTTP method requirements
            if ($result = $route->getRequirement('_method')) {
                // HEAD and GET are equivalent as per RFC
                $method = $this->context->getMethod();

                if ($method === 'HEAD') {
                    $method = 'GET';
                }

                if (in_array($method, $result = explode('|', strtoupper($result)))) {
                    $this->allow = array_merge($this->allow, $result);

                    continue;
                }
            }

            return array_merge($this->mergeDefaults($matches, $route->getDefaults()), array('_route' => $name));
        }
    }

    protected function mergeDefaults($params, $defaults)
    {
        $parameters = $defaults;

        foreach ($params as $key => $value) {
            if (!is_int($key)) {
                $parameters[$key] = rawurldecode($value);
            }
        }

        return $parameters;
    }
}