<?php

namespace Ignite\Http\File\Exception;

class FileNotFoundException extends FileException
{
    /**
     * Constructor.
     *
     * @param string $path The path to the file that was not found
     */
    public function __construct(string $path)
    {
        parent::__construct(sprintf('The file "%s" does not exist.', $path));
    }
}