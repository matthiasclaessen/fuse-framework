<?php

namespace Ignite\Routing;

/**
 * The RequestContext class holds information about the current request.
 *
 * @author Matthias Claessen
 */
class RequestContext
{
    private string $baseUrl;
    private string $method;
    private string $host;
    private string $scheme;
    private int $httpPort;
    private int $httpsPort;
    private array $parameters;

    /**
     * Constructor.
     *
     * @param string $baseUrl The base URL
     * @param string $method The HTTP method
     * @param string $host The HTTP host name
     * @param string $scheme The HTTP scheme
     * @param int $httpPort The HTTP port
     * @param int $httpsPort The HTTPS port
     */
    public function __construct(string $baseUrl = '', string $method = 'GET', string $host = 'localhost', string $scheme = 'http', int $httpPort = 80, int $httpsPort = 443)
    {
        $this->baseUrl = $baseUrl;
        $this->method = strtoupper($method);
        $this->host = $host;
        $this->scheme = strtolower($scheme);
        $this->httpPort = $httpPort;
        $this->httpsPort = $httpsPort;
        $this->parameters = [];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = strtoupper($method);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function setScheme(string $scheme): void
    {
        $this->scheme = strtolower($scheme);
    }

    public function getHttpPort(): int
    {
        return $this->httpPort;
    }

    public function setHttpPort(int $httpPort): void
    {
        $this->httpPort = $httpPort;
    }

    public function getHttpsPort(): int
    {
        return $this->httpsPort;
    }

    public function setHttpsPort(int $httpsPort): void
    {
        $this->httpPort = $httpsPort;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameter(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function setParameter(string $name, mixed $parameter): static
    {
        $this->parameters[$name] = $parameter;

        return $this;
    }

    public function isSecure(): bool
    {
        return $this->scheme === 'https';
    }
}