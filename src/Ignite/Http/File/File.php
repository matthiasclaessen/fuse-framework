<?php

namespace Ignite\Http\File;

use Ignite\Http\File\Exception\FileException;
use Ignite\Http\File\Exception\FileNotFoundException;
use Ignite\Http\File\MimeType\MimeTypeGuesser;
use SplFileInfo;

/**
 * A file in the file system.
 *
 * @author Matthias Claessen
 */
class File extends SplFileInfo
{
    protected static $defaultExtensions = array(
        'application/andrew-inset' => 'ez',
        'application/appledouble' => 'base64',
        'application/applefile' => 'base64',
        'application/commonground' => 'dp',

    );

    /**
     * Construct a new file from the given path.
     *
     * @param string $path The path to the file
     * @param bool $checkPath Whether to check the path or not
     *
     * @throws FileNotFoundException The exception that will be thrown when the given path is not a file
     */
    public function __construct(string $path, bool $checkPath = true)
    {
        if ($checkPath && !is_file($path)) {
            throw new FileNotFoundException($path);
        }

        parent::__construct($path);
    }

    /**
     * Return the extension based on the mime type. If the mime type is unknown, return null.
     *
     * @return string|null The guessed extension or null if it cannot be guessed
     */
    public function guessExtension(): ?string
    {
        $type = $this->getMimeType();

        return static::$defaultExtensions[$type] ?? null;
    }

    /**
     * Return the mime type of the file.
     *
     * The mime type is guessed using the functions finfo(), mime_content_type()
     * and the system binary "file" (in this order), depending on which of those
     * is available on the current operating system.
     *
     * @return string|null The guessed mime type (i.e. "application/pdf")
     */
    public function getMimeType(): ?string
    {
        $guesser = MimeTypeGuesser::getInstance();

        return $guesser->guess($this->getPathname());
    }

    /**
     * Return the extension of the file.
     *
     * SplFileInfo::getExtension() is not available before PHP 5.3.6
     *
     * @return string The extension
     */
    public function getExtension(): string
    {
        return pathinfo($this->getBasename(), PATHINFO_EXTENSION);
    }

    /**
     * Move the file to a new location.
     *
     * @param string $directory The destination folder
     * @param string|null $name The new file name
     *
     * @return File A File object representing the new file
     *
     * @throws FileException The exception that will be thrown when the target file could not be created
     */
    public function move(string $directory, string $name = null): File
    {
        $target = $this->getTargetFile($directory, $name);

        if (!@rename($this->getPathname(), $target)) {
            $error = error_get_last();

            throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s).', $this->getPathname(), $target, strip_tags($error['message'])));
        }

        chmod($target, 0666 & ~umask());

        return $target;
    }

    public function getTargetFile($directory, $name = null): File
    {
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true)) {
                throw new FileException(sprintf('Unable to create the "%s" directory.', $directory));
            }
        } else if (!is_writable($directory)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory.', $directory));
        }

        $target = $directory . DIRECTORY_SEPARATOR . (null === $name ? $this->getBasename() : $this->getName($name));

        return new File($target, false);
    }

    /**
     * Return locale independent base name of the given path.
     *
     * @param string $name The new file name
     *
     * @return string
     */
    public function getName(string $name): string
    {
        $originalName = str_replace('\\', '/', $name);
        $position = strrpos($originalName, '/');
        $originalName = false === $position ? $originalName : substr($originalName, $position + 1);

        return $originalName;
    }
}