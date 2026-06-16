<?php
namespace App\Model\Source;

class SourceRegistry
{
    private array $sources;

    public function __construct(array $sources)
    {
        $this->sources = $sources;
    }

    public function getAll(): array
    {
        return $this->sources;
    }

    public function get(string $key): array
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException('Unknown source');
        }

        return $this->sources[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->sources[$key]);
    }
}