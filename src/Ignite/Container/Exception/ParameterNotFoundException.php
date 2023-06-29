<?php

namespace Ignite\Container\Exception;

/**
 * This exception is thrown when a non-existent parameter is used.
 *
 * @author Matthias Claessen
 */
class ParameterNotFoundException extends InvalidArgumentException
{
    private string $key;
    private ?string $sourceId;
    private ?string $sourceKey;


    /**
     * Constructor
     *
     * @param string $key The requested parameter key
     * @param string $sourceId The service id that references the non-existent parameter
     * @param string $sourceKey The parameter key that references the non-existent parameter
     */
    public function __construct(string $key, $sourceId = null, $sourceKey = null)
    {
        $this->key = $key;
        $this->sourceId = $sourceId;
        $this->sourceKey = $sourceKey;

        $this->updateErrorMessage();
    }

    public function updateErrorMessage(): void
    {
        if ($this->sourceId !== null) {
            $this->message = sprintf('The service "%s" has a dependency on a non-existent parameter "%s".', $this->sourceId, $this->key);
        } else if ($this->sourceKey !== null) {
            $this->message = sprintf('The parameter "%s" has a dependency on a non-existent parameter "%s".', $this->sourceKey, $this->key);
        } else {
            $this->message = sprintf('You have requested a non-existent parameter "%s"', $this->key);
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId($sourceId): void
    {
        $this->sourceId = $sourceId;

        $this->updateErrorMessage();
    }

    public function getSourceKey(): ?string
    {
        return $this->sourceKey;
    }

    public function setSourceKey($sourceKey): void
    {
        $this->sourceKey = $sourceKey;

        $this->updateErrorMessage();
    }

}