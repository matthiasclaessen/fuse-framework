<?php

namespace Ignite\Http;

use DateTime;
use RuntimeException;

/**
 * HeaderContainer is a container for HTTP headers.
 *
 * @author Matthias Claessen
 */
class HeaderContainer
{
    protected array $headers;
    protected array $cacheControl;

    public function __construct(array $headers = [])
    {
        $this->cacheControl = [];
        $this->headers = [];

        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    public function __toString()
    {
        if (!$this->headers) {
            return '';
        }

        $beautifier = function ($name) {
            return preg_replace_callback('/\-(.)/', function ($match) {
                return '-' . strtoupper($match[1]);
            }, ucfirst($name));
        };

        $max = max(array_map('strlen', array_keys($this->headers))) + 1;

        $content = '';

        ksort($this->headers);

        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $beautifier($name) . ':' . $value);
            }
        }

        return $content;
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function keys(): array
    {
        return array_keys($this->headers);
    }

    public function replace(array $headers = []): void
    {
        $this->headers = [];
        $this->add($headers);
    }

    public function add(array $headers): void
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    public function get($key, $default = null, $first = true)
    {
        $key = strtr(strtolower($key), '_', '-');

        if (!array_key_exists($key, $this->headers)) {
            if ($default === null) {
                return $first ? null : [];
            }

            return $first ? $default : [$default];
        }

        if ($first) {
            return count($this->headers[$key]) ? $this->headers[$key][0] : $default;
        }

        return $this->headers[$key];
    }

    public function set(string $key, string|array $values, bool $replace = true): void
    {
        $key = strtr(strtolower($key), '_', '-');

        if ($replace === true || !isset($this->headers[$key])) {
            $this->headers[$key] = $values;
        } else {
            $this->headers[$key] = array_merge($this->headers[$key], $values);
        }

        if ($key === 'cache-control') {
            $this->cacheControl = $this->parseCacheControl($values[0]);
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists(strtr(strtolower($key), '_', '-'), $this->headers);
    }

    public function contains($key, $value): bool
    {
        return in_array($value, $this->get($key, null, false));
    }

    public function remove(string $key): void
    {
        $key = strtr(strtolower($key), '_', '-');

        unset($this->headers[$key]);

        if ($key === 'cache-control') {
            $this->cacheControl = [];
        }
    }

    public function getDate($key, DateTime $default = null): ?DateTime
    {
        $value = $this->get($key);
        $date = DateTime::createFromFormat(DATE_RFC2822, $value);

        if ($value === null) {
            return $default;
        }

        if ($date === false) {
            throw new RuntimeException(sprintf('The %s HTTP header is not parseable (%s).', $key, $value));
        }

        return $date;
    }

    public function addCacheControlDirective($key, $value = true): void
    {
        $this->cacheControl[$key] = $value;

        $this->set('Cache-Control', $this->getCacheControlHeader());
    }

    public function hasCacheControlDirective($key): bool
    {
        return array_key_exists($key, $this->cacheControl);
    }

    public function getCacheControlDirective($key)
    {
        return array_key_exists($key, $this->cacheControl) ? $this->cacheControl[$key] : null;
    }

    public function removeCacheControlDirective($key): void
    {
        unset($this->cacheControl[$key]);

        $this->set('Cache-Control', $this->getCacheControlHeader());
    }

    protected function getCacheControlHeader(): string
    {
        $parts = [];

        ksort($this->cacheControl);

        foreach ($this->cacheControl as $key => $value) {
            if ($value === true) {
                $parts[] = $key;
            } else {
                if (preg_match('#[^a-zA-Z0-9._-]#', $value)) {
                    $value = '"' . $value . '"';
                }

                $parts[] = "$key=$value";
            }
        }

        return implode(', ', $parts);
    }


    /**
     * Parse a Cache-Control HTTP Header
     *
     * @param string $header The value of the Cache-Control HTTP header
     * @return array An array representing the attribute value
     */
    public function parseCacheControl(string $header): array
    {
        $cacheControl = [];

        preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?#', $header, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $cacheControl[strtolower($match[1])] = isset($match[2]) && $match[2] ? $match[2] : ($match[3] ?? true);
        }

        return $cacheControl;
    }
}