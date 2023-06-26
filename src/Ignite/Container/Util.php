<?php

namespace Ignite\Container;

class Util
{
    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param mixed $value
     * @return array
     */
    public static function arrayWrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
}