<?php

namespace Lighten\Http;

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
}