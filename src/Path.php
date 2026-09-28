<?php

namespace SageCounseling\Helpers;

class Path
{
    /**
     * Joins path segments with DIRECTORY_SEPARATOR, as given (no trimming or normalizing).
     * Replaces rps's file_build_path().
     */
    public static function join(string ...$segments): string
    {
        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}
