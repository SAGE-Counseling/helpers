<?php

namespace SageCounseling\Helpers\Tests;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Path;

class PathTest extends TestCase
{
    public function test_join_puts_the_directory_separator_between_segments(): void
    {
        $sep = DIRECTORY_SEPARATOR;

        $this->assertSame("storage{$sep}app{$sep}report.csv", Path::join('storage', 'app', 'report.csv'));
        $this->assertSame('solo', Path::join('solo'));
    }
}
