<?php

namespace Ignite\Http\File\MimeType;

use Ignite\Http\File\Exception\AccessDeniedException;
use Ignite\Http\File\Exception\FileNotFoundException;

/**
 * Guesses the mime type using the PHP function mime_content_type().
 *
 * @author Matthias Claessen
 */
class ContentTypeMimeTypeGuesser implements MimeTypeGuesserInterface
{
    /**
     * Check whether this guesser is supported on the current OS/PHP setup.
     *
     * @return bool
     */
    public static function isSupported(): bool
    {
        return function_exists('mime_content_type');
    }

    /**
     * Guess the mime type of the file with the given path.
     *
     * @see MimeTypeGuesserInterface::guess()
     */
    public function guess($path)
    {
        if (!is_file($path)) {
            throw new FileNotFoundException($path);
        }

        if (!is_readable($path)) {
            throw new AccessDeniedException($path);
        }

        if (!self::isSupported()) {
            return null;
        }

        $type = mime_content_type($path);

        // Remove charset (added as of PHP 5.3)
        $position = strpos($type, ';');

        if ($position !== false) {
            $type = substr($type, 0, $position);
        }

        return $type;
    }
}