<?php

namespace Ignite\Routing;

interface RequestContextAwareInterface
{
    /**
     * Set the request context.
     *
     * @param RequestContext $context The context
     */
    public function setContext(RequestContext $context);
}