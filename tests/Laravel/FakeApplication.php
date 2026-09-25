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

    /** @var array<string, callable> */
    private array $factories = [];

    public function __construct()
    {
        $this->config = new FakeConfigRepository();
    }

    /**
     * Registers a fake instance to return for a given abstract, so tests can
     * hand the provider a fake Mailer/HttpPoster without a real container.
     */
    public function bind(string $abstract, mixed $instance): void
    {
        $this->bindings[$abstract] = $instance;
    }

    /**
     * Minimal stand-in for Container::singleton(): stores a factory, resolved
     * (and memoized) on first make() unless a test has already bind()'d a
     * fake for the same abstract.
     */
    public function singleton(string $abstract, callable $concrete): void
    {
        $this->factories[$abstract] = $concrete;
    }

    public function make(string $abstract): mixed
    {
        if ($abstract === 'config') {
            return $this->config;
        }

        if (array_key_exists($abstract, $this->bindings)) {
            return $this->bindings[$abstract];
        }

        if (isset($this->factories[$abstract])) {
            return $this->bindings[$abstract] = ($this->factories[$abstract])($this);
        }

        return $this->config;
    }

    public function configPath(string $path = ''): string
    {
        return '/fake-app/config/'.$path;
    }
}
