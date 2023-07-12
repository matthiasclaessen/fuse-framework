<?php

namespace Ignite\Http\SessionStorage;

/**
 * SessionStorageInterface
 *
 * @author Matthias Claessen
 */

interface SessionStorageInterface
{
    public function start();

    public function getId();

    public function read($key);

    public function remove($key);

    public function write($key, $data);

    public function regenerate($destroy = false);
}