<?php

namespace Ignite\Http;

class Request
{
    protected $query;
    protected $request;
    protected $files;
    protected $server;
    protected $headers;

    public function __construct(array $query = [], array $request = [], array $files = [], array $server = [], array $headers = [])
    {
        $this->query = $query;
        $this->request = $request;
        $this->files = $files;
        $this->server = $server;
        $this->headers = $headers;
    }

    public static function capture()
    {
        return new static($_GET, $_POST, $_FILES, $_SERVER, getallheaders());
    }

    public function query($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->request;
        }

        return $this->request[$key] ?? $default;
    }

    public function file($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->files;
        }

        return $this->files[$key] ?? $default;
    }

    public function server($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->server;
        }

        return $this->server[$key] ?? $default;
    }

    public function header($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->headers;
        }

        return $this->headers[$key] ?? $default;
    }
}