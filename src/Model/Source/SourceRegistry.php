<?php

namespace App\Model\Source;

use InvalidArgumentException;

/**
 * Stores and provides access to configured data sources.
 */
class SourceRegistry
{
    private array $sources;

    /**
     * Accepts source configuration array from config/sources.php.
     */
    public function __construct(array $sources)
    {
        $this->sources = $sources;
    }

    /**
     * Returns all configured sources.
     */
    public function getAll(): array
    {
        return $this->sources;
    }

    /**
     * Returns source configuration by key.
     */
    public function get(string $key): array
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException('Unknown source: ' . $key);
        }

        return $this->sources[$key];
    }

    /**
     * Checks whether source exists in configuration.
     */
    public function has(string $key): bool
    {
        return isset($this->sources[$key]);
    }
}