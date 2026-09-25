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

    public function __construct()
    {
        $this->config = new FakeConfigRepository();
    }

    public function make(string $abstract): FakeConfigRepository
    {
        return $this->config;
    }

    public function configPath(string $path = ''): string
    {
        return '/fake-app/config/'.$path;
    }
}
