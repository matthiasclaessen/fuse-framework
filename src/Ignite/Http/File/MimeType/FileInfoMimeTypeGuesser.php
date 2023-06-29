<?php

namespace Ignite\Http\File\MimeType;

use finfo;
use Ignite\Http\File\Exception\AccessDeniedException;
use Ignite\Http\File\Exception\FileNotFoundException;

/**
 * Guess the mime type using the PECL extension FileInfo
 *
 * @author Matthias Claessen
 */
class FileInfoMimeTypeGuesser implements MimeTypeGuesserInterface
{
    /**
     * Return whether this guesser is supported on the current OS/PHP setup
     *
     * @return bool
     */
    public static function isSupported(): bool
    {
        return function_exists('finfo_open');
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

        if (!$fileInfo = new finfo(FILEINFO_MIME_TYPE)) {
            return null;
        }

        return $fileInfo->file($path);
    }
}