<?php

namespace SageCounseling\Helpers\Tests\Redaction;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Redaction\SqlMessageRedactor;

class SqlMessageRedactorTest extends TestCase
{
    public function test_strip_sql_suffix_replaces_the_connection_and_sql_detail(): void
    {
        $message = "SQLSTATE[23000]: Integrity constraint violation (Connection: mysql, SQL: insert into clients (ssn) values ('123-45-6789'))";

        $this->assertSame(
            'SQLSTATE[23000]: Integrity constraint violation [SQL detail redacted — see sql/bindings fields]',
            SqlMessageRedactor::stripSqlSuffix($message)
        );
    }

    public function test_strip_sql_suffix_handles_the_short_form_without_a_connection_name(): void
    {
        $this->assertSame(
            'Deadlock found [SQL detail redacted — see sql/bindings fields]',
            SqlMessageRedactor::stripSqlSuffix("Deadlock found (SQL: update clients set name = 'Jo')")
        );
    }

    public function test_strip_sql_suffix_leaves_a_message_without_sql_alone(): void
    {
        $this->assertSame('Connection refused', SqlMessageRedactor::stripSqlSuffix('Connection refused'));
    }
}
