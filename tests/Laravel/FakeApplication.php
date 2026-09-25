<?php

namespace SageCounseling\Helpers\Tests\Laravel;

/**
 * Minimal stand-in for Illuminate\Contracts\Foundation\Application, covering
 * only what SageHelpersServiceProvider and its Illuminate\Support\ServiceProvider
 * parent actually call (Application's constructor param is untyped, so any
 * object duck-typing these methods works without pulling in a full Laravel
 * application via orchestra/testbench).
 */
class FakeApplication
{
    public FakeConfigRepository $config;

    /** @var array<string, mixed> */
    private array $bindings = [];

    public function __construct()
    {
        $this->config = new FakeConfigRepository();
    }

    /**
     * Registers a fake to return for a given abstract, so tests can hand the
     * provider a fake Mailer/HTTP client without a real container.
     */
    public function bind(string $abstract, mixed $instance): void
    {
        $this->bindings[$abstract] = $instance;
    }

    public function make(string $abstract): mixed
    {
        if ($abstract === 'config') {
            return $this->config;
        }

        return $this->bindings[$abstract] ?? $this->config;
    }

    public function configPath(string $path = ''): string
    {
        return '/fake-app/config/'.$path;
    }
}
