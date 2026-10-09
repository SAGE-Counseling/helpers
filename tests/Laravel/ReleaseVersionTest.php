<?php

namespace SageCounseling\Helpers\Tests\Laravel;

use ArrayAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Laravel\ReleaseVersion;

class ReleaseVersionTest extends TestCase
{
    private string $versionFile;

    private FakeConfigRepository $config;

    protected function setUp(): void
    {
        $this->versionFile = tempnam(sys_get_temp_dir(), 'version');

        // label() and number() resolve config and the app through facades; point them at fakes so no
        // full Laravel application is needed.
        $this->config = new FakeConfigRepository();
        $app = new class($this->config) implements ArrayAccess {
            public function __construct(private FakeConfigRepository $config)
            {
            }

            public function version(): string
            {
                return '13.0.0-test';
            }

            public function offsetExists(mixed $offset): bool
            {
                return in_array($offset, ['config', 'app'], true);
            }

            public function offsetGet(mixed $offset): mixed
            {
                return $offset === 'config' ? $this->config : $this;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
            }

            public function offsetUnset(mixed $offset): void
            {
            }
        };

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        @unlink($this->versionFile);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function test_first_deploy_of_a_month_is_01(): void
    {
        $now = CarbonImmutable::create(2026, 10, 8);

        $this->assertSame('2026.10.01', ReleaseVersion::next([], $now));
        $this->assertSame('2026.10.01', ReleaseVersion::next(['2026.09.01', '2026.09.02'], $now));
    }

    public function test_counter_follows_the_highest_tag_of_the_month(): void
    {
        $now = CarbonImmutable::create(2026, 10, 8);

        $this->assertSame('2026.10.03', ReleaseVersion::next(['2026.10.01', '2026.10.02'], $now));
        $this->assertSame('2026.10.10', ReleaseVersion::next(['2026.10.09', '2026.10.02'], $now));
    }

    public function test_counter_goes_past_two_digits_without_wrapping(): void
    {
        $this->assertSame('2026.10.100', ReleaseVersion::next(['2026.10.99'], CarbonImmutable::create(2026, 10, 8)));
    }

    public function test_month_is_zero_padded(): void
    {
        $this->assertSame('2027.01.01', ReleaseVersion::next([], CarbonImmutable::create(2027, 1, 2)));
    }

    public function test_other_tags_are_ignored(): void
    {
        $tags = ['v1.0.0', '2026.10.x', 'release-2026.10.05', '2026.10.04-hotfix', '2026.10.02'];

        $this->assertSame('2026.10.03', ReleaseVersion::next($tags, CarbonImmutable::create(2026, 10, 8)));
    }

    public function test_reads_the_version_written_by_the_deploy(): void
    {
        file_put_contents($this->versionFile, "2026.10.01\n");

        $this->assertSame('2026.10.01', ReleaseVersion::fromFile($this->versionFile));
    }

    public function test_falls_back_to_dev_when_no_version_was_written(): void
    {
        $this->assertSame('dev', ReleaseVersion::fromFile($this->versionFile));
        $this->assertSame('dev', ReleaseVersion::fromFile($this->versionFile.'-missing'));
    }

    public function test_the_app_reports_its_version_and_the_laravel_version(): void
    {
        $this->config->set('app.version', '2026.10.01');

        $this->assertSame('v2026.10.01 · Laravel 13.0.0-test', ReleaseVersion::label());

        $this->config->set('app.version', 'dev');

        $this->assertSame('dev · Laravel 13.0.0-test', ReleaseVersion::label());
    }

    public function test_number_is_the_version_alone_without_laravel(): void
    {
        $this->config->set('app.version', '2026.10.01');

        $this->assertSame('v2026.10.01', ReleaseVersion::number());
    }

    public function test_number_is_dev_when_no_version_file_was_written(): void
    {
        $this->config->set('app.version', 'dev');

        $this->assertSame('dev', ReleaseVersion::number());
    }

    public function test_number_is_dev_when_the_version_is_not_configured(): void
    {
        $this->assertSame('dev', ReleaseVersion::number());
    }
}
