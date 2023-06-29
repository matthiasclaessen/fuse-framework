<?php

namespace Ignite\Http\File\MimeType;

use Ignite\Http\File\Exception\AccessDeniedException;
use Ignite\Http\File\Exception\FileNotFoundException;

/**
 * Guesses the mime type of file
 *
 * @author Matthias Claessen
 */

interface MimeTypeGuesserInterface
{
    /**
     * Guess the mime type of the file with the given path.
     *
     * @param $path
     *
     * @return string The mime type or NULL, if none could be guessed
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws AccessDeniedException If the file could not be read
     */
    public function guess($path);
}