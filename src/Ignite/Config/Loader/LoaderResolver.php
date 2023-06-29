<?php

namespace Ignite\Config\Loader;

class LoaderResolver implements LoaderResolverInterface
{
    /**
     * @var LoaderInterface[] An array of LoaderInterface objects
     */
    private array $loaders;

    public function __construct(array $loaders = [])
    {
        $this->loaders = [];
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    public function resolve($resource, $type = null)
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($resource, $type)) {
                return $loader;
            }
        }

        return false;
    }

    /**
     * Add a loader.
     *
     * @param LoaderInterface $loader A LoaderInterface instance.
     * @return void
     */
    public function addLoader(LoaderInterface $loader): void
    {
        $this->loaders[] = $loader;
        $loader->setResolver($this);
    }

    public function getLoaders(): array
    {
        return $this->loaders;
    }
}