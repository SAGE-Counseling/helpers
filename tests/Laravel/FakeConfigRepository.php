<?php

namespace SageCounseling\Helpers\Tests\Laravel;

/**
 * Minimal stand-in for Illuminate\Config\Repository, just enough for
 * Illuminate\Support\ServiceProvider::mergeConfigFrom() to operate against.
 */
class FakeConfigRepository
{
    private array $items = [];

    public function set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }
}
