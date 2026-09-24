<?php

namespace SageCounseling\Helpers;

class Str
{
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = preg_replace('/[^a-zA-Z0-9]+/', $separator, $value);

        return trim(strtolower($value), $separator);
    }
}
