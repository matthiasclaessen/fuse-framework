<?php

namespace Ignite\Container;

/**
 * Scope Interface
 *
 * @author Matthias Claessen
 */
interface ScopeInterface
{
    public function getName();

    public function getParentName();
}