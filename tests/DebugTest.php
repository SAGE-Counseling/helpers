<?php

namespace SageCounseling\Helpers\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Debug;
use SageCounseling\Helpers\Tests\Laravel\FakeConfigRepository;
use SageCounseling\Helpers\Tests\Notifications\SpyLogger;

class DebugTest extends TestCase
{
    private SpyLogger $log;

    protected function setUp(): void
    {
        $this->log = new SpyLogger();
        Log::swap($this->log);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
    }

    public function test_log_writes_a_debug_line_when_app_debug_is_on(): void
    {
        $this->appDebug(true);

        Debug::log('About to push client to Credible');

        $this->assertSame(['About to push client to Credible'], $this->log->debugs);
    }

    public function test_log_writes_nothing_when_app_debug_is_off_or_unset(): void
    {
        $this->appDebug(false);
        Debug::log('hidden');
        Config::swap(new FakeConfigRepository());
        Debug::log('hidden too');

        $this->assertSame([], $this->log->debugs);
    }

    public function test_here_logs_the_calling_class_and_method(): void
    {
        $this->appDebug(true);

        Debug::here();

        $this->assertSame(['In: '.self::class.'::test_here_logs_the_calling_class_and_method'], $this->log->debugs);
    }

    public function test_here_logs_nothing_when_app_debug_is_off(): void
    {
        $this->appDebug(false);

        Debug::here();

        $this->assertSame([], $this->log->debugs);
    }

    private function appDebug(bool $on): void
    {
        $config = new FakeConfigRepository();
        $config->set('app.debug', $on);
        Config::swap($config);
    }
}
