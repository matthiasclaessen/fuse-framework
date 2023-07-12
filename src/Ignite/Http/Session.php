<?php

namespace Ignite\Http;

use Exception;
use Ignite\Http\SessionStorage\SessionStorageInterface;
use Locale;

class Session implements \Serializable
{
    protected SessionStorageInterface $storage;
    protected array $attributes;
    protected array $flashes;
    protected array $oldFlashes;
    protected string $locale;
    protected string $defaultLocale;
    protected bool $started;
    protected bool $closed;

    public function __construct(SessionStorageInterface $storage, $defaultLocale = 'en')
    {
        $this->storage = $storage;
        $this->defaultLocale = $defaultLocale;
        $this->locale = $defaultLocale;
        $this->flashes = [];
        $this->oldFlashes = [];
        $this->attributes = [];
        $this->setPhpDefaultLocale($this->defaultLocale);
        $this->started = false;
        $this->closed = false;
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $this->storage->start();

        $attributes = $this->storage->read('_fuse');

        if (isset($attributes['attributes'])) {
            $this->attributes = $attributes['attributes'];
            $this->flashes = $attributes['flashes'];
            $this->locale = $attributes['locale'];
            $this->setPhpDefaultLocale($this->locale);

            // Flag current flash messages to be removed at shutdown
            $this->oldFlashes = $this->flashes;
        }

        $this->started = true;
    }

    public function save(): void
    {
        if ($this->started === false) {
            $this->start();
        }

        $this->flashes = array_diff_key($this->flashes, $this->oldFlashes);

        $this->storage->write('_fuse', array(
            'attributes' => $this->attributes,
            'flashes' => $this->flashes,
            'locale' => $this->locale
        ));
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function serialize(): ?string
    {
        return serialize(array($this->storage, $this->defaultLocale));
    }

    public function unserialize(string $data): void
    {
        list($this->storage, $this->defaultLocale) = unserialize($data);
        $this->attributes = [];
        $this->started = false;
    }


    private function setPhpDefaultLocale($locale): void
    {
        try {
            if (class_exists('Locale', false)) {
                Locale::setDefault($locale);
            }
        } catch (Exception $exception) {
            // TODO: Throw correct exception
            echo $exception;
        }
    }
}