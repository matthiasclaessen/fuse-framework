<?php

namespace Ignite\Config\Loader;

abstract class Loader implements LoaderInterface
{
    protected $resolver;

    public function getResolver()
    {
        return $this->resolver;
    }

    public function setResolver(LoaderResolver $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function import($resource, $type = null)
    {
        return $this->resolve($resource)->load($resource, $type);
    }

    public function resolve($resource, $type = null)
    {
        if ($this->supports($resource, $type)) {
            return $this;
        }

        $loader = null === $this->resolver ? false : $this->resolver->resolve($resource, $type);

        if ($loader === false) {
            // TODO: throw new FileLoaderLoadException($resource)
        }

        return $loader;
    }
}