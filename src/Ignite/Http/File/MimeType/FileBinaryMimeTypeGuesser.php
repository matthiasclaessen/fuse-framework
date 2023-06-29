<?php

namespace Ignite\Http\File\MimeType;

use Ignite\Http\File\Exception\AccessDeniedException;
use Ignite\Http\File\Exception\FileNotFoundException;

/**
 * Guesses the mime type with the binary "file" (only available on *nix)
 *
 * @author Matthias Claessen
 */
class FileBinaryMimeTypeGuesser implements MimeTypeGuesserInterface
{
    /**
     * Return whether this guesser is supported on the current OS
     *
     * @return bool
     */
    public static function isSupported(): bool
    {
        return !defined('PHP_WINDOWS_VERSION_BUILD') && function_exists('passthru') && function_exists('escapeshellarg');
    }

    /**
     * Guess the mime type of the file with the given path
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

        ob_start();

        // Need to use --mime instead of -i. See #6641
        passthru(sprintf('File -b --mime %s 2>/dev/null', escapeshellarg($path)), $return);

        if ($return > 0) {
            ob_end_clean();

            return null;
        }

        $type = trim(ob_get_clean());

        if (!preg_match('#^([a-z0-9\-]+/[a-z0-9\-\.]+)#i', $type, $match)) {
            // It's not a type, but an error message
            return null;
        }

        return $match[1];
    }
}