<?php

namespace SageCounseling\Helpers\Redaction;

/**
 * Truncates a credential (e.g. a Credible Export Key) to a fixed number of trailing characters
 * behind a literal "..." prefix, so it can be logged or named for correlation without exposing the
 * usable secret. A value no longer than $keep comes back whole behind the "...".
 * Ported from bi-reflector.
 */
class CredentialRedactor
{
    public static function last(string $value, int $keep = 10): string
    {
        return '...'.substr($value, -$keep);
    }
}
