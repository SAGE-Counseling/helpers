<?php

namespace SageCounseling\Helpers\Laravel;

use DateTimeInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * The calendar version (YYYY.MM.NN) a production deploy assigns, tags in git and writes to the release's
 * VERSION file. NN counts deploys within the month, starting at 01. See docs/deploy-versioning.md.
 */
class ReleaseVersion
{
    public const Fallback = 'dev';

    /**
     * The version the next deploy in the month of $now takes, given the repository's existing tags.
     *
     * @param  array<int, string>  $existingTags
     */
    public static function next(array $existingTags, DateTimeInterface $now): string
    {
        $month = $now->format('Y.m');
        $highest = 0;

        foreach ($existingTags as $tag) {
            if (preg_match('/^'.preg_quote($month, '/').'\.(\d+)$/', $tag, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        return sprintf('%s.%02d', $month, $highest + 1);
    }

    /**
     * The version in a release's VERSION file, or the fallback where no deploy wrote one (local, dev).
     */
    public static function fromFile(string $path): string
    {
        $version = is_file($path) ? trim((string) file_get_contents($path)) : '';

        return $version !== '' ? $version : self::Fallback;
    }

    /**
     * The running app and Laravel versions, for display to signed-in users.
     * Reads config('app.version'): "v2026.10.01 · Laravel 13.x.y", or "dev · Laravel 13.x.y".
     */
    public static function label(): string
    {
        return self::number().' · Laravel '.App::version();
    }

    /**
     * The running app's version alone, for pages external users see: "v2026.10.01", or "dev" when
     * config('app.version') is the fallback or not set. Leaves out the Laravel version.
     */
    public static function number(): string
    {
        $version = Config::get('app.version');

        if ($version === null || $version === '' || $version === self::Fallback) {
            return self::Fallback;
        }

        return 'v'.$version;
    }
}
