<?php

namespace Ignite\Routing;

use Ignite\Routing\Generator\UrlGeneratorInterface;
use Ignite\Routing\Matcher\UrlMatcherInterface;

/**
 * RouterInterface is the interface that all Router classes must implement.
 *
 * This interface is the concatenation of UrlMatcherInterface and UrlGeneratorInterface.
 *
 * @author Matthias Claessen
 */

interface RouterInterface extends UrlMatcherInterface, UrlGeneratorInterface
{

}