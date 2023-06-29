<?php

namespace Ignite\Http\File;

use Ignite\Http\File\Exception\FileException;

class UploadedFile extends File
{
    private bool $test = false;
    private string $originalName;
    private string $mimeType;
    private string $size;
    private int $error;

    public function __construct($path, $originalName, $mimeType = null, $size = null, $error = null, $test = false)
    {
        if (!ini_get('file_uploads')) {
            throw new FileException(sprintf('Unable to create UploadedFile because "file_uploads" is disabled in your php.ini file (%s).', get_cfg_var('cfg_file_path')));
        }

        $this->originalName = $this->getName($originalName);
        $this->mimeType = $mimeType ?? 'application/octet-stream';
        $this->size = $size;
        $this->error = $error ?? UPLOAD_ERR_OK;
        $this->test = $test;

        parent::__construct($path, UPLOAD_ERR_OK === $this->error);
    }

    public function getClientOriginalName(): string|null
    {
        return $this->originalName;
    }

    public function getClientMimeType()
    {
        return $this->mimeType;
    }

    public function getClientSize()
    {
        return $this->size;
    }

    public function getError()
    {
        return $this->error;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Move the file to a new location.
     *
     * @param string $directory The destination folder
     * @param string $name The new file name
     *
     * @return File A File object representing the new file
     *
     * @throws FileException The exception that will be thrown when the file has not been uploaded via HTTP
     */
    public function move(string $directory, string $name = null): File
    {
        if ($this->isValid()) {
            if ($this->test) {
                return parent::move($directory, $name);
            } else if (is_uploaded_file($this->getPathname())) {
                $target = $this->getTargetFile($directory, $name);

                if (!@move_uploaded_file($this->getPathname(), $target)) {
                    $error = error_get_last();
                    throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error['message'])));
                }

                @chmod($target, 0666 & ~umask());

                return $target;
            }
        }

        throw new FileException(sprintf('The file "%s" has not been uploaded via HTTP', $this->getPathname()));
    }

    /**
     * Return the maximum size of an uploaded file as configured in php.ini
     *
     * @return int The maximum size of an uploaded file in bytes
     */
    public static function getMaxFileSize(): int
    {
        $max = trim(ini_get('upload_max_filesize'));

        if ($max === '') {
            return PHP_INT_MAX;
        }

        switch (strtolower(substr($max, -1))) {
            case 'g':
                $max *= 1024;
            case 'm':
                $max *= 1024;
            case 'k':
                $max *= 1024;
        }

        return (int)$max;
    }

}