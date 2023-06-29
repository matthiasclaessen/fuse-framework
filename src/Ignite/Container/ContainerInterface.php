<?php

namespace Ignite\Container;

interface ContainerInterface
{
    public const EXCEPTION_ON_INVALID_REFERENCE = 1;
    public const NULL_ON_INVALID_REFERENCE = 2;
    public const IGNORE_ON_INVALID_REFERENCE = 3;
    public const SCOPE_CONTAINER = 'container';
    public const SCOPE_PROTOTYPE = 'prototype';

    public function set(string $id, object $service, string $scope = self::SCOPE_CONTAINER);

    public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE);

    public function has(string $id);

    public function getParameter(string $name);

    public function hasParameter(string $name);

    public function setParameter(string $name, mixed $value);

    public function enterScope(string $name);

    public function leaveScope(string $name);

    public function addScope(ScopeInterface $scope);

    public function hasScope(string $name);

    public function isScopeActive(string $name);
}