<?php

namespace Ignite\Config\Loader;

interface LoaderResolverInterface
{
    /**
     * Return a loader able to load the resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return LoaderInterface A LoaderInterface instance
     */
    public function resolve(mixed $resource, string $type = null);
}