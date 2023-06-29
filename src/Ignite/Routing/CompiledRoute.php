<?php

namespace Ignite\Routing;


/**
 * CompiledRoutes are returned by the RouteCompiler class.
 *
 * @author Matthias Claessen
 */
class CompiledRoute
{
    private $route;
    private $variables;
    private $tokens;
    private $staticPrefix;
    private $regularExpression;

    public function __construct(Route $route, $staticPrefix, $regularExpression, array $tokens, array $variables)
    {
        $this->route = $route;
        $this->staticPrefix = $staticPrefix;
        $this->regularExpression = $regularExpression;
        $this->tokens = $tokens;
        $this->variables = $variables;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getStaticPrefix()
    {
        return $this->staticPrefix;
    }

    public function getRegularExpression()
    {
        return $this->regularExpression;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getPattern(): string
    {
        return $this->route->getPattern();
    }

    public function getOptions(): array
    {
        return $this->route->getOptions();
    }

    public function getDefaults(): array
    {
        return $this->route->getDefaults();
    }

    public function getRequirements(): array
    {
        return $this->route->getRequirements();
    }
}