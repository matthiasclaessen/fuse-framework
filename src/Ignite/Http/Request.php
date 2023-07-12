<?php

namespace Ignite\Http;

class Request
{
    protected string $method;

    public function __construct()
    {

    }

    public function getMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'];

        return $method;
    }

    public function getBasePath()
    {
        $basePath = $_SERVER['REQUEST_URI'];

        return $basePath;
    }

}