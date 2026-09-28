<?php

namespace SageCounseling\Helpers\Tests;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Csv;

class CsvTest extends TestCase
{
    public function test_row_formats_one_escaped_csv_line(): void
    {
        $this->assertSame(
            "plain,\"has, comma\",\"say \"\"hi\"\"\",42\n",
            Csv::row(['plain', 'has, comma', 'say "hi"', 42]),
        );
    }

    public function test_rows_formats_each_row_as_its_own_escaped_line(): void
    {
        $this->assertSame(
            "id,name\n1,\"Doe, Jane\"\n",
            Csv::rows([['id', 'name'], [1, 'Doe, Jane']]),
        );
    }

    public function test_rows_of_nothing_is_empty(): void
    {
        $this->assertSame('', Csv::rows([]));
    }

    public function test_quoted_row_wraps_every_value_in_quotes_without_escaping(): void
    {
        $this->assertSame(
            '"a","b, c","1"',
            Csv::quotedRow(['a', 'b, c', 1]),
        );
    }

    public function test_quoted_row_uses_an_objects_public_properties(): void
    {
        $this->assertSame(
            '"7","Jane"',
            Csv::quotedRow((object) ['id' => 7, 'name' => 'Jane']),
        );
    }

    public function test_quoted_rows_ends_every_quoted_row_with_crlf(): void
    {
        $this->assertSame(
            "\"1\",\"x\"\r\n\"2\",\"y\"\r\n",
            Csv::quotedRows([[1, 'x'], [2, 'y']]),
        );
    }
}
