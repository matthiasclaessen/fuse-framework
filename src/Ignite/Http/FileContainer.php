<?php

namespace Ignite\Http;

/**
 * FileContainer is a container for HTTP headers.
 *
 * @author Matthias Claessen
 */
class FileContainer extends ParameterContainer
{
    private static $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];

    public function __construct(array $parameters = [])
    {
        $this->replace($parameters);
    }

    public function replace(array $files = []): void
    {
        $this->parameters = [];
        $this->add($files);
    }

    /**
     * @see ParameterContainer::set()
     */
    public function set($key, $value)
    {
        if (is_array($value) || $value instanceof UploadedFile) {
            parent::set($key, $this->convertFileInformation($value));
        } else {
            throw new \InvalidArgumentException('An uploaded file must be an array or an instance of UploadedFile');
        }
    }
}