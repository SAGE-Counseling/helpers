<?php

namespace SageCounseling\Helpers;

use Carbon\Carbon;

class Dates
{
    /**
     * Arizona doesn't observe DST, so this is a fixed UTC-7 year-round. Use the
     * named zone rather than a hand-rolled offset.
     */
    public const TIMEZONE = 'America/Phoenix';

    /**
     * Convert a UTC timestamp (e.g. from an external API) to Phoenix time.
     */
    public static function fromUtc(string|\DateTimeInterface $value): Carbon
    {
        return Carbon::parse($value, 'UTC')->setTimezone(self::TIMEZONE);
    }

    /**
     * Format a date as Y-m-d. The value is not converted between timezones;
     * use fromUtc() first for UTC input.
     */
    public static function showDate(string|\DateTimeInterface|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return Carbon::parse($date)->toDateString();
    }

    /**
     * Format a date as Y-m-d H:i:s. The value is not converted between
     * timezones; use fromUtc() first for UTC input.
     */
    public static function showDateTime(string|\DateTimeInterface|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return Carbon::parse($date)->toDateTimeString();
    }
}
