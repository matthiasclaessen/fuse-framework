<?php

namespace Ignite\Http;

use UnexpectedValueException;

/**
 * Request represents an HTTP request.
 *
 * @author Matthias Claessen
 */
class Request
{
    const HEADER_CLIENT_IP = 'client_ip';
    const HEADER_CLIENT_HOST = 'client_host';
    const HEADER_CLIENT_PROTO = 'client_proto';
    const HEADER_CLIENT_PORT = 'client_port';

    protected static bool $trustProxy = false;
    protected static array $trustedProxies = [];
    protected static array $trustedHostPatterns = [];
    protected static array $trustedHosts = [];

    /**
     * Names for headers that can be trusted when using trusted proxies.
     * The default names are non-standard, but widely used by popular reverse proxies (like Apache mod_proxy).
     */
    protected static array $trustedHeaders = array(
        self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
        self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST',
        self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT'
    );

    /**
     * @var ParameterContainer
     */
    public ParameterContainer $attributes;

    /**
     * @var ParameterContainer
     */
    public ParameterContainer $request;

    /**
     * @var ParameterContainer
     */
    public ParameterContainer $query;

    /**
     * @var ServerContainer
     */
    public ServerContainer $server;

    /**
     * @var FileContainer
     */
    public FileContainer $files;

    /**
     * @var ParameterContainer
     */
    public ParameterContainer $cookies;

    /**
     * @var HeaderContainer
     */
    public HeaderContainer $headers;

    protected string $content;
    protected array $languages;
    protected array $charsets;
    protected array $acceptableContentTypes;
    protected string $pathInfo;
    protected string $requestUri;
    protected string $baseUrl;
    protected string $basePath;
    protected string $method;
    protected string $format;
    protected Session $session;

    protected static array $formats;

    /**
     * Constructor.
     *
     * @param array $query The GET parameters
     * @param array $request The POST parameters
     * @param array $attributes The request attributes (parameters parsed from the PATH_INFO,...)
     * @param array $cookies The COOKIE parameters
     * @param array $files The FILES parameters
     * @param array $server The SERVER parameters
     * @param string|null $content The raw body data
     */
    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], string $content = null)
    {
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    /**
     * Set the parameters for this request.
     *
     * This method also re-initializes all properties.
     *
     * @param array $query The GET parameters
     * @param array $request The POST parameters
     * @param array $attributes The request attributes (parameters parsed from the PATH_INFO,...)
     * @param array $cookies The COOKIE parameters
     * @param array $files The FILES parameters
     * @param array $server The SERVER parameters
     * @param string|null $content The raw body data
     *
     * @return void
     */
    public function initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], string $content = null): void
    {
        $this->request = new ParameterContainer($request);
        $this->query = new ParameterContainer($query);
        $this->attributes = new ParameterContainer($attributes);
        $this->cookies = new ParameterContainer($cookies);
        $this->files = new FileContainer($files);
        $this->server = new ServerContainer($server);
        $this->headers = new HeaderContainer($this->server->getHeaders());

        $this->content = $content;
        $this->languages = [];
        $this->charsets = [];
        $this->acceptableContentTypes = [];
        $this->pathInfo = null;
        $this->requestUri = null;
        $this->baseUrl = null;
        $this->basePath = null;
        $this->method = null;
        $this->format = null;
    }

    /**
     * Create a new request with values from PHP's super globals.
     *
     * @return Request A new request
     */
    public static function createFromGlobals(): Request
    {
        $request = new static($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);

        if (str_starts_with($request->server->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterContainer($data);
        }

        return $request;
    }

    /**
     * Create a Request based on a given URI and configuration.
     *
     * @param string $uri The URI
     * @param string $method The HTTP method
     * @param array $parameters The request (GET) or query (POST) parameters
     * @param array $cookies The request cookies ($_COOKIE)
     * @param array $files The request files ($_FILES)
     * @param array $server The server parameters ($_SERVER)
     * @param string|null $content The raw body data
     *
     * @return Request A Request instance
     */
    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], string $content = null): Request
    {
        $defaults = array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => '',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time()
        );

        $components = parse_url($uri);

        if (isset($components['host'])) {
            $defaults['SERVER_NAME'] = $components['host'];
            $defaults['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $defaults['HTTPS'] = 'on';
                $defaults['SERVER_PORT'] = 443;
            }
        }

        if (isset($components['port'])) {
            $defaults['SERVER_PORT'] = $components['port'];
            $defaults['HTTP_HOST'] = $defaults['HTTP_HOST'] . ':' . $components['port'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '';
        }

        if (in_array(strtoupper($method), array('POST', 'PUT', 'DELETE'))) {
            $request = $parameters;
            $query = [];
            $defaults['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        } else {
            $request = [];
            $query = $parameters;
            $position = strpos($uri, '?');

            if ($position !== false) {
                $qs = substr($uri, $position + 1);
                parse_str($qs, $params);

                $query = array_merge($params, $query);
            }
        }

        $queryString = isset($components['query']) ? html_entity_decode($components['query']) : '';
        parse_str($queryString, $qs);

        if (is_array($qs)) {
            $query = array_replace($qs, $query);
        }

        $uri = $components['path'] . ($queryString ? '?' . $queryString : '');

        $server = array_replace($defaults, $server, array(
            'REQUEST_METHOD' => strtoupper($method),
            'PATH_INFO' => '',
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => $queryString,
        ));

        return new static($query, $request, array(), $cookies, $files, $server, $content);
    }

    /**
     * Clone a request and override some of its parameters
     *
     * @param array|null $query The GET parameters
     * @param array|null $request The POST parameters
     * @param array|null $attributes The request attributes (parameters parsed from the PATH_INFO,...)
     * @param array|null $cookies The COOKIE parameters
     * @param array|null $files The FILES parameters
     * @param array|null $server The SERVER parameters
     */
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null): Request
    {
        $duplicate = clone $this;

        if ($query !== null) {
            $duplicate->query = new ParameterContainer($query);
        }

        if ($request !== null) {
            $duplicate->request = new ParameterContainer($request);
        }

        if ($attributes !== null) {
            $duplicate->attributes = new ParameterContainer($attributes);
        }

        if ($cookies !== null) {
            $duplicate->cookies = new ParameterContainer($cookies);
        }

        if ($files !== null) {
            $duplicate->files = new FileContainer($files);
        }

        if ($server !== null) {
            $duplicate->server = new ServerContainer($server);
            $duplicate->headers = new HeaderContainer($duplicate->server->getHeaders());
        }

        $duplicate->languages = null;
        $duplicate->charsets = null;
        $duplicate->acceptableContentTypes = null;
        $duplicate->pathinfo = null;
        $duplicate->requestUri = null;
        $duplicate->baseUrl = null;
        $duplicate->basePath = null;
        $duplicate->method = null;
        $duplicate->format = null;

        return $duplicate;
    }

    public function __clone()
    {
        $this->query = clone $this->query;
        $this->request = clone $this->request;
        $this->attributes = clone $this->attributes;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
        $this->server = clone $this->server;
        $this->headers = clone $this->headers;
    }

    public function __toString()
    {
        return sprintf('%s %s %s', $this->getMethod(), $this->getRequestUri(),
                $this->server->get('SERVER_PROTOCOL')) . "\r\n" . $this->headers . "\r\n" . $this->getContent();
    }


    public function overrideGlobals(): void
    {
        $_GET = $this->query->all();
        $_POST = $this->request->all();
        $_SERVER = $this->server->all();
        $_COOKIE = $this->cookies->all();

        // TODO: Populate $_FILES

        foreach ($this->headers->all() as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));

            if (in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
                $_SERVER[$key] = implode(', ', $value);
            } else {
                $_SERVER['HTTP_' . $key] = implode(', ', $value);
            }
        }

        $_REQUEST = array_merge($_GET, $_POST);
    }

    public static function setTrustedProxies(array $proxies): void
    {
        self::$trustedProxies = $proxies;
        self::$trustProxy = (bool)$proxies;
    }

    public static function setTrustedHosts(array $hostPatterns): void
    {
        self::$trustedHostPatterns = array_map(function ($hostPattern) {
            return sprintf('{%s}i', str_replace('}', '\\}', $hostPattern));
        }, $hostPatterns);

        self::$trustedHosts = [];
    }

    public static function getTrustedHosts(): array
    {
        return self::$trustedHostPatterns;
    }

    public static function setTrustedHeaderName($key, $value): void
    {
        if (!array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf('Unable to set the trusted header name for key "%s".', $key));
        }

        self::$trustedHeaders[$key] = $value;
    }

    public function get($key, $default = null, $deep = false): mixed
    {
        return $this->query->get($key, $this->attributes->get($key, $this->request->get($key, $default, $deep), $deep), $deep);
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function hasPreviousSession(): bool
    {
        return $this->cookies->has(session_name()) && $this->session !== null;
    }

    public function hasSession(): bool
    {
        return $this->session !== null;
    }

    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    // TODO: Implement getClientIp method

    public function getScriptName(): string
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }

    public function getPathInfo()
    {
        if ($this->pathInfo === null) {
            $this->pathInfo = $this->preparePathInfo();
        }

        return $this->pathInfo;
    }

    public function getBasePath()
    {
        if ($this->basePath === null) {
            $this->basePath = $this->prepareBasePath();
        }

        return $this->basePath;
    }

    public function getBaseUrl()
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = $this->prepareBaseUrl();
        }

        return $this->baseUrl;
    }

    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function getPort()
    {
        if (self::$trustProxy && self::$trustedHeaders[self::HEADER_CLIENT_PORT] && $port = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PORT])) {
            return $port;
        }

        return $this->server->get('SERVER_PORT');
    }

    public function getHttpHost(): string
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' == $scheme && $port = 80) || ('https' == $scheme && $port == 443)) {
            return $this->getHost();
        }

        return $this->getHost() . ':' . $port;
    }

    public function getRequestUri()
    {
        if ($this->requestUri === null) {
            $this->requestUri = $this->prepareRequestUri();
        }

        return $this->requestUri;
    }

    public function getUri(): string
    {
        $qs = $this->getQueryString();

        if ($qs !== null) {
            $qs = '?' . $qs;
        }

        return $this->getScheme() . '://' . $this->getHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $qs;
    }

    public function getUriForPath(string $path): string
    {
        return $this->getScheme() . '://' . $this->getHttpHost() . $this->getBaseUrl() . $path;
    }

    public function getQueryString(): string|null
    {
        if (!$qs = $this->server->get('QUERY_STRING')) {
            return null;
        }

        $parts = [];
        $order = [];

        foreach (explode('&', $qs) as $segment) {
            if (strpos($segment, '=' == false)) {
                $parts[] = $segment;
                $order[] = $segment;
            } else {
                $tmp = explode('=', rawurldecode($segment), 2);
                $parts[] = rawurlencode($tmp[0]) . '=' . rawurlencode($tmp[1]);
                $order[] = $tmp[0];
            }
        }

        array_multisort($order, SORT_ASC, $parts);

        return implode('&', $parts);
    }

    public function isSecure(): bool
    {
        if (self::$trustProxy && self::$trustedHeaders[self::HEADER_CLIENT_PROTO] && $proto = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PROTO])) {
            return in_array(strtolower($proto), array('https', 'on', '1'));
        }

        return 'on' == strtolower($this->server->get('HTTPS')) || 1 == $this->server->get('HTTPS');
    }

    /**
     * Return the host name.
     *
     * This method can read the client port from the "X-Forwarded-Host" header when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Host" header must contain the client host name.
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Host", configure it via "setTrustedHeaderName()" with the "client-host" key.
     *
     * @return string
     *
     * @throws UnexpectedValueException The exception thrown when the host name is invalid
     */
    public function getHost(): string
    {
        if (self::$trustProxy && self::$trustedHeaders[self::HEADER_CLIENT_HOST] && $host = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_HOST])) {
            $elements = explode(',', $host);

            $host = $elements[count($elements) - 1];
        } elseif (!$host = $this->headers->get('HOST')) {
            if (!$host = $this->server->get('SERVER_NAME')) {
                $host = $this->server->get('SERVER_ADDRESS', '');
            }
        }

        // Trim and remove port number from host
        $host = preg_replace('/:\d+$/', '', trim($host));

        // Check if host does not contain forbidden characters
        if ($host && !preg_match('/^\[?(?:[a-zA-Z0-9-:\\]_]+\.?)+$/', $host)) {
            throw new UnexpectedValueException('Invalid Host');
        }

        if (count(self::$trustedHostPatterns) > 0) {
            // To avoid host header injection attacks, you should provide a list of trusted host patterns
            if (in_array($host, self::$trustedHosts)) {
                return $host;
            }

            foreach (self::$trustedHostPatterns as $pattern) {
                if (preg_match($pattern, $host)) {
                    self::$trustedHosts[] = $host;

                    return $host;
                }
            }

            throw new UnexpectedValueException('Untrusted Host');
        }

        return $host;
    }

    public function setMethod(string $method): void
    {
        $this->method = null;
        $this->server->set('REQUEST_METHOD', $method);
    }

    public function getMethod(): string
    {
        if ($this->method === null) {
            $this->method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
            if ($this->method === 'POST') {
                $this->method = strtoupper($this->headers->get('X-HTTP-METHOD-OVERRIDE', $this->request->get('_method', 'POST')));
            }
        }

        return $this->method;
    }

    public function getMimeType($format)
    {
        if (static::$formats === null) {
            static::initializeFormats();
        }

        return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
    }

    public function getFormat($mimeType): string
    {
        $position = strpos($mimeType, ';');

        if ($position !== false) {
            $mimeType = substr($mimeType, 0, $position);
        }

        if (static::$formats === null) {
            static::initializeFormats();
        }

        foreach (static::$formats as $format => $mimeTypes) {
            if (in_array($mimeType, (array)$mimeTypes)) {
                return $format;
            }
        }

        return '';
    }


    /*
    |--------------------------------------------------------------
    | Protected Functions
    |--------------------------------------------------------------
    */

    protected function prepareRequestUri()
    {
        $requestUri = '';

        if ($this->headers->has('X_ORIGINAL_URL') && false !== stripos(PHP_OS, 'WIN')) {
            $requestUri = $this->headers->get('X_ORIGINAL_URL');
            $this->headers->remove('X_ORIGINAL_URL');
        }

        // Normalize the request URI to ease creating sub-requests from this request
        $this->server->set('REQUEST_URI', $requestUri);

        return $requestUri;
    }

    protected function prepareBaseUrl()
    {
        $fileName = basename($this->server->get('SCRIPT_FILENAME'));

        if (basename($this->server->get('SCRIPT_NAME')) == $fileName) {
            $baseUrl = $this->server->get('SCRIPT_NAME');
        } elseif (basename($this->server->get('PHP_SELF')) === $fileName) {
            $baseUrl = $this->server->get('PHP_SELF');
        } elseif (basename($this->server->get('ORIG_SCRIPT_NAME')) === $fileName) {
            $baseUrl = $this->server->get('ORIG_SCRIPT_NAME');
        } else {
            $path = $this->server->get('PHP_SELF', '');
            $file = $this->server->get('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $baseUrl = '';

            do {
                $seg = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
            } while (($last > $index) && (false !== ($position = strpos($path, $baseUrl))) && (0 != $position));
        }

        $requestUri = $this->getRequestUri();

        if ($baseUrl && str_starts_with($requestUri, $baseUrl)) {
            // Full $baseUrl matches
            return $baseUrl;
        }

        if ($baseUrl && str_starts_with($requestUri, dirname($baseUrl))) {
            return rtrim(dirname($baseUrl, '/'));
        }

        $truncatedRequestUri = $requestUri;

        if (($position = strpos($requestUri, '?')) !== false) {
            $truncatedRequestUri = substr($requestUri, 0, $position);
        }

        $baseName = basename($baseUrl);

        if (empty($baseName) || !strpos($truncatedRequestUri, $baseName)) {
            // No match whatsoever, set it blank
            return '';
        }

        return rtrim($baseUrl, '/');
    }


    protected function prepareBasePath(): string
    {
        $fileName = basename($this->server->get('SCRIPT_FILENAME'));
        $baseUrl = $this->getBaseUrl();

        if (empty($baseUrl)) {
            $basePath = dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return rtrim($basePath, '/');
    }


    protected function preparePathInfo()
    {
        $baseUrl = $this->getBaseUrl();
        $requestUri = $this->getRequestUri();

        if ($requestUri === null) {
            return '/';
        }

        $pathInfo = '/';

        // Remove query string from REQUEST_URI
        if ($position = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $position);
        }

        if (($baseUrl !== null) && !($pathInfo = substr(urldecode($requestUri), strlen(urldecode($baseUrl))))) {
            // If substr() returns false, then PATH_INFO is set to an empty string
            return '/';
        } elseif ($baseUrl === null) {
            return $requestUri;
        }

        return (string)$pathInfo;
    }

    protected static function initializeFormats(): void
    {
        static::$formats = array(
            'html' => array('text/html', 'application/xhtml+xml'),
            'text' => array('text/plain'),
            'js' => array('application/javascript', 'application/x-javascript', 'text/javascript'),
            'css' => array('text/css'),
            'json' => array('application/json', 'application/x-json'),
            'xml' => array('text/xml', 'application/xml', 'application/x-xml'),
            'rdf' => array('application/rdf-xml'),
            'atom' => array('application/atom+xml'),
        );
    }
}