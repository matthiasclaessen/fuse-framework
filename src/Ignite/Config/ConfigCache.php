<?php

namespace Ignite\Config;

use RuntimeException;

class ConfigCache
{
    private bool $debug;
    private string $file;

    /**
     * Constructor.
     *
     * @param string $file The absolute cache path
     * @param bool $debug Whether debugging is enabled or not
     */
    public function __construct(string $file, bool $debug)
    {
        $this->file = $file;
        $this->debug = $debug;
    }

    /**
     * Get the cache file path.
     *
     * @return string The cache file path
     */
    public function __toString()
    {
        return $this->file;
    }

    public function isFresh(): bool
    {
        if (!file_exists($this->file)) {
            return false;
        }

        if (!$this->debug) {
            return true;
        }

        $metadata = $this->file . '.meta';

        if (!file_exists($metadata)) {
            return false;
        }

        $time = filemtime($this->file);
        $meta = unserialize(file_get_contents($metadata));

        foreach ($meta as $resource) {
            if (!$resource->isFresh($time)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Write cache
     *
     * @param string $content The content to write in the cache
     * @param array|null $metadata An array of ResourceInterface instances
     *
     * @throws RuntimeException The exception that will be thrown when the cache file can't be written
     */
    public function write(string $content, array $metadata = null)
    {
        $directory = dirname($this->file);

        if (!is_dir($directory)) {
            if (@mkdir($directory, 0777, true) === false) {
                throw new RuntimeException(sprintf('Unable to create the "%s" directory.', $directory));
            }
        } else if (!is_writable($directory)) {
            throw new RuntimeException(sprintf('Unable to write in the "%s" directory.', $directory));
        }

        $temporaryFile = tempnam(dirname($this->file), basename($this->file));

        if (@file_put_contents($temporaryFile, $content) !== false && @rename($temporaryFile, $this->file)) {
            chmod($this->file, 0666);
        } else {
            throw new RuntimeException(sprintf('Failed to write cache file "%s".', $this->file));
        }

        if ($metadata !== null && $this->debug === true) {
            $file = $this->file . '.meta';
            $temporaryFile = tempnam(dirname($file), basename($file));

            if (@file_put_contents($temporaryFile, serialize($metadata)) !== false && @rename($temporaryFile, $file)) {
                chmod($file, 0666);
            }
        }
    }
}