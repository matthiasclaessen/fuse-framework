<?php

namespace Ignite\Config\Loader;

interface LoaderInterface
{
    public function load($resource, $type = null);

    public function supports($resource, $type = null);

    public function getResolver();

    public function setResolver(LoaderResolver $resolver);
}