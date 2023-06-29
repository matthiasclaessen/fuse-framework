<?php

namespace Ignite\Http\File\MimeType;

use Ignite\Http\File\Exception\AccessDeniedException;
use Ignite\Http\File\Exception\FileException;
use Ignite\Http\File\Exception\FileNotFoundException;

/**
 * A singleton mime type guesser.
 *
 * By default, all mime type guessers provided by the framework are installed (if available on the current OS/PHP setup).
 * You can register custom guessers by calling the register() method on the singleton instance.
 *
 * The last registered guesser is preferred over previously registered ones.
 *
 * @author Matthias Claessen
 *
 */
class MimeTypeGuesser implements MimeTypeGuesserInterface
{
    /**
     * The singleton instance
     *
     * @var MimeTypeGuesser
     */
    private static ?MimeTypeGuesser $instance = null;

    /**
     * All the registered MimeTypeGuesserInterface instances
     *
     * @var array
     */
    protected array $guessers = [];

    /**
     * Return the singleton instance
     *
     * @return MimeTypeGuesser
     */
    public static function getInstance(): MimeTypeGuesser
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register all natively provided mime type guessers
     */
    private function __construct()
    {
        // File Binary
        if (FileBinaryMimeTypeGuesser::isSupported()) {
            $this->register(new FileBinaryMimeTypeGuesser());
        }

        // Content Type
        if (ContentTypeMimeTypeGuesser::isSupported()) {
            $this->register(new ContentTypeMimeTypeGuesser());
        }

        // File Info
        if (FileInfoMimeTypeGuesser::isSupported()) {
            $this->register(new FileInfoMimeTypeGuesser());
        }
    }

    public function register(MimeTypeGuesserInterface $guesser): void
    {
        array_unshift($this->guessers, $guesser);
    }

    /**
     * Tries to guess the mime type of the given file
     *
     * The file is passed to each registered mime type guesser in reverse order of their registration (last registered is queried first).
     * Once a guesser returns a value that is not NULL, this method terminates and returns the value.
     *
     * @param string $path The path to the file
     *
     * @return string The mime type or NULL, if none could be guessed
     *
     * @throws FileException If the file does not exist
     */
    public function guess($path): string
    {
        if (!is_file($path)) {
            throw new FileNotFoundException($path);
        }

        if (!is_readable($path)) {
            throw new AccessDeniedException($path);
        }

        if (!$this->guessers) {
            throw new \LogicException('Unable to guess the mime type as no guessers are available. (Did you enable the php_fileinfo extension?');
        }

        foreach ($this->guessers as $guesser) {
            $mimeType = $guesser->guess($path);

            if ($mimeType !== null) {
                return $mimeType;
            }
        }
    }
}