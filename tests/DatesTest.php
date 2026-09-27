<?php

namespace SageCounseling\Helpers\Tests;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Dates;

class DatesTest extends TestCase
{
    public function test_timezone_is_america_phoenix(): void
    {
        $this->assertSame('America/Phoenix', Dates::TIMEZONE);
    }

    public function test_from_utc_converts_string_to_phoenix_time(): void
    {
        $date = Dates::fromUtc('2026-01-15 20:30:00');

        $this->assertSame('America/Phoenix', $date->getTimezone()->getName());
        $this->assertSame('2026-01-15 13:30:00', $date->toDateTimeString());
    }

    public function test_from_utc_uses_fixed_offset_during_us_daylight_saving_time(): void
    {
        // July: most of the US is on DST, Arizona is not — still UTC-7, not UTC-6.
        $date = Dates::fromUtc('2026-07-15 20:30:00');

        $this->assertSame('2026-07-15 13:30:00', $date->toDateTimeString());
    }

    public function test_from_utc_rolls_back_across_midnight(): void
    {
        $date = Dates::fromUtc('2026-03-10 03:00:00');

        $this->assertSame('2026-03-09 20:00:00', $date->toDateTimeString());
    }

    public function test_from_utc_accepts_datetime_interface(): void
    {
        $utc = new \DateTimeImmutable('2026-01-15 20:30:00', new \DateTimeZone('UTC'));

        $date = Dates::fromUtc($utc);

        $this->assertSame('2026-01-15 13:30:00', $date->toDateTimeString());
    }

    public function test_from_utc_returns_carbon_instance(): void
    {
        $this->assertInstanceOf(Carbon::class, Dates::fromUtc('2026-01-15 20:30:00'));
    }

    public function test_show_date_parses_string_dates(): void
    {
        $this->assertSame('2026-01-15', Dates::showDate('2026-01-15 13:30:00'));
    }

    public function test_show_date_formats_datetime_instances(): void
    {
        $date = new \DateTimeImmutable('2026-01-15 13:30:00', new \DateTimeZone(Dates::TIMEZONE));

        $this->assertSame('2026-01-15', Dates::showDate($date));
    }

    public function test_show_date_returns_empty_string_for_null_or_empty(): void
    {
        $this->assertSame('', Dates::showDate(null));
        $this->assertSame('', Dates::showDate(''));
    }

    public function test_show_date_time_parses_string_dates(): void
    {
        $this->assertSame('2026-01-15 13:30:00', Dates::showDateTime('2026-01-15 13:30:00'));
    }

    public function test_show_date_time_formats_datetime_instances(): void
    {
        $date = new \DateTimeImmutable('2026-01-15 13:30:00', new \DateTimeZone(Dates::TIMEZONE));

        $this->assertSame('2026-01-15 13:30:00', Dates::showDateTime($date));
    }

    public function test_show_date_time_returns_empty_string_for_null_or_empty(): void
    {
        $this->assertSame('', Dates::showDateTime(null));
        $this->assertSame('', Dates::showDateTime(''));
    }
}
