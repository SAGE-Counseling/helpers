<?php

namespace SageCounseling\Helpers;

class Str
{
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = preg_replace('/[^a-zA-Z0-9]+/', $separator, $value);

        return trim(strtolower($value), $separator);
    }

    /**
     * Converts studly/camel case (or spaced words) to snake case, matching Illuminate's Str::snake()
     * without depending on it: "AversysId" becomes "aversys_id". An all-lowercase value is returned as is.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        if (ctype_lower($value)) {
            return $value;
        }

        $value = preg_replace('/\s+/u', '', ucwords($value));

        return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value), 'UTF-8');
    }

    /**
     * Strips HTML tags from $haystack, then wraps every case-insensitive match of $term in a bold
     * colored <span>, keeping the matched text's own case. Invalid UTF-8 comes back unhighlighted.
     * Replaces rps's highlight(). Output is HTML meant for {!! !!}; only tags are stripped, so don't
     * pass it untrusted text expecting full escaping.
     */
    public static function highlight(?string $haystack, ?string $term): string
    {
        $haystack = strip_tags($haystack ?? '');

        if ($term === null || $term === '') {
            return $haystack;
        }

        return preg_replace(
            '/'.preg_quote($term, '/').'/iu',
            '<span style="font-weight: bold; color: #DA70D6;">$0</span>',
            $haystack
        ) ?? $haystack;
    }
}
