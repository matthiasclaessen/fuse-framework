<?php

namespace Ignite\Routing\Generator;

use Ignite\Routing\Exception\InvalidParameterException;
use Ignite\Routing\Exception\MissingMandatoryParametersException;
use Ignite\Routing\Exception\RouteNotFoundException;
use Ignite\Routing\RequestContext;
use Ignite\Routing\RouteCollection;

/**
 * UrlGenerator generates URL based on a set of routes.
 */
class UrlGenerator implements UrlGeneratorInterface
{
    protected RequestContext $context;
    protected array $decodedCharacters = array(
        '%2F' => '/',
    );

    protected RouteCollection $routes;
    protected array $cache;

    /**
     * Constructor.
     *
     * @param RouteCollection $routes A RouteCollection instance
     * @param RequestContext $context The context
     */
    public function __construct(RouteCollection $routes, RequestContext $context)
    {
        $this->routes = $routes;
        $this->context = $context;
        $this->cache = [];
    }

    /**
     * Set the request context.
     *
     * @param RequestContext $context The context
     *
     * @return void
     */
    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    /**
     * Get the request context.
     *
     * @return RequestContext The context
     */
    public function getContext(): RequestContext
    {
        return $this->context;
    }

    /**
     * Generate a URL from the given parameters.
     *
     * @param string $name The name of the route
     * @param mixed $parameters An array of parameters
     * @param bool $absolute Whether to generate an absolute URL
     *
     * @return string
     *
     * @throws RouteNotFoundException The exception that will be thrown when the route does not exist
     */
    public function generate(string $name, mixed $parameters = [], bool $absolute = false): string
    {
        $route = $this->routes->get($name);

        if ($route === null) {
            throw new RouteNotFoundException(sprintf('Route "%s" does not exist.', $name));
        }

        if (!isset($this->cache[$name])) {
            $this->cache[$name] = $route->compile();
        }

        return $this->doGenerate($this->cache[$name]->getVariables(), $route->getDefaults(), $route->getRequirements(), $this->cache[$name]->getTokens(), $parameters, $name, $absolute);
    }

    /**
     * @param $variables
     * @param $defaults
     * @param $requirements
     * @param $tokens
     * @param $parameters
     * @param $name
     * @param $absolute
     *
     * @return string
     *
     * @throws MissingMandatoryParametersException The exception that will be thrown when the route has some missing mandatory parameters
     * @throws InvalidParameterException The exception that will be thrown when a parameter value is not correct
     */
    protected function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $absolute): string
    {
        $variables = array_flip($variables);

        $originParameters = $parameters;
        $parameters = array_replace($this->context->getParameters(), $parameters);
        $temporaryParameters = array_replace($defaults, $parameters);

        // All parameters must be given
        if ($difference = array_diff_key($variables, $temporaryParameters)) {
            throw new MissingMandatoryParametersException(sprintf('The "%s" route has some missing mandatory parameters ("%s").', $name, implode('", "', array_keys($difference))));
        }

        $url = '';
        $optional = true;

        foreach ($tokens as $token) {
            $tokenType = $token[0];
            $tokenValue = $token[1];
            $tokenOptional = $token[2];
            $tokenAttributes = $token[3];


            if ($tokenType === 'variable') {
                if ($optional === false || array_key_exists($tokenAttributes, $defaults) || (isset($parameters[$tokenAttributes]) && (string)$parameters[$tokenAttributes] != (string)$defaults[$tokenAttributes])) {
                    $isEmpty = in_array($temporaryParameters[$tokenAttributes], array(null, '', false), true);

                    if (!$isEmpty) {
                        // Check requirement
                        if ($temporaryParameters[$tokenAttributes] && !preg_match('#^' . $tokenOptional . '$#', $temporaryParameters[$tokenAttributes])) {
                            throw new InvalidParameterException(sprintf('Parameter "%s" for route "%s" must match "%s" ("%s" given).', $tokenAttributes, $tokenOptional, $temporaryParameters[$tokenAttributes]));
                        }
                    }

                    if (!$isEmpty || !$optional) {
                        $url = $tokenValue . strtr(rawurlencode($temporaryParameters[$tokenAttributes]), $this->decodedCharacters) . $url;
                    }

                    $optional = false;
                }
            } else if ($tokenType === 'text') {
                $url = $tokenValue . $url;
                $optional = false;
            }
        }

        if (!$url) {
            $url = '/';
        }

        // Add a query string if needed
        $extra = array_diff_key($originParameters, $variables, $defaults);
        $query = http_build_query($extra, '', '&');

        if ($extra && $query) {
            $url .= '?' . $query;
        }

        $url = $this->context->getBaseUrl() . $url;

        if ($this->context->getHost()) {
            $scheme = $this->context->getScheme();

            if (isset($requirements['_scheme']) && ($req = strtolower($requirements['_scheme'])) && $scheme != $req) {
                $absolute = true;
                $scheme = $req;
            }

            if ($absolute) {
                $port = '';

                if ($scheme === 'http' && $this->context->getHttpPort() != 80) {
                    $port = ':' . $this->context->getHttpPort();
                } else if ($scheme === 'https' && $this->context->getHttpsPort() != 443) {
                    $port = ':' . $this->context->getHttpsPort();
                }

                $url = $scheme . '://' . $this->context->getHost() . $port . $url;
            }
        }

        return $url;
    }
}