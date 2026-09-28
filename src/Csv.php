<?php

namespace SageCounseling\Helpers;

class Csv
{
    /**
     * Formats one row as an escaped CSV line (via fputcsv), ending in "\n", without writing a file.
     * Replaces rps's filterToCsv().
     */
    public static function row(array $fields): string
    {
        return self::format([$fields]);
    }

    /**
     * Formats each row as its own escaped CSV line (via fputcsv), without writing a file.
     * Replaces rps's filterAllToCsv().
     *
     * @param  array<array>  $rows
     */
    public static function rows(array $rows): string
    {
        return self::format($rows);
    }

    /**
     * Wraps every value in double quotes and joins them with commas, with no trailing newline.
     * Embedded quotes are NOT escaped — use row() for untrusted text. An object is first converted
     * through its JSON form (public properties, or jsonSerialize() e.g. an Eloquent model).
     * Replaces rps's make_csv().
     */
    public static function quotedRow(array|object $fields): string
    {
        if (is_object($fields)) {
            $fields = json_decode(json_encode($fields), true);
        }

        return '"'.implode('","', $fields).'"';
    }

    /**
     * Formats each row with quotedRow() and ends every line (including the last) with "\r\n".
     * Replaces rps's makeFullCsv().
     *
     * @param  array<array|object>  $rows
     */
    public static function quotedRows(array $rows): string
    {
        $csv = '';

        foreach ($rows as $row) {
            $csv .= self::quotedRow($row)."\r\n";
        }

        return $csv;
    }

    /**
     * Formats rows through fputcsv on an in-memory stream and returns the text. The escape character is
     * passed explicitly (fputcsv's historical default) so output matches rps and PHP 8.4+ doesn't warn.
     */
    private static function format(array $rows): string
    {
        $stream = fopen('php://temp', 'w+');

        foreach ($rows as $row) {
            fputcsv($stream, $row, ',', '"', '\\');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
