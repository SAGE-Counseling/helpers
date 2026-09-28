<?php

namespace SageCounseling\Helpers\Redaction;

/**
 * Strips the inlined SQL suffix that Laravel's QueryException::getMessage() appends
 * (`"<message> (Connection: <name>, SQL: <statement with bindings inlined>)"`), replacing it
 * with a short fixed note. Detection is content-based (looks for a literal marker substring)
 * rather than `instanceof QueryException`, so it also catches wrapped/re-thrown exceptions that
 * still carry that suffix, or hand-built messages using the shorter `" (SQL: "` form with no
 * connection name. Ported from rps/bi-reflector.
 */
class SqlMessageRedactor
{
    private const CONNECTION_SQL_SUFFIX_MARKER = ' (Connection: ';

    private const SQL_SUFFIX_MARKER = ' (SQL: ';

    private const REDACTION_NOTE = ' [SQL detail redacted — see sql/bindings fields]';

    public static function stripSqlSuffix(string $message): string
    {
        $position = strpos($message, self::CONNECTION_SQL_SUFFIX_MARKER);

        if ($position === false) {
            $position = strpos($message, self::SQL_SUFFIX_MARKER);
        }

        if ($position === false) {
            return $message;
        }

        return substr($message, 0, $position).self::REDACTION_NOTE;
    }
}
