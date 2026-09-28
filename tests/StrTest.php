<?php

namespace SageCounseling\Helpers\Tests;

use PHPUnit\Framework\TestCase;
use SageCounseling\Helpers\Str;

class StrTest extends TestCase
{
    public function test_slug_converts_to_lowercase_with_separator(): void
    {
        $this->assertSame('hello-world', Str::slug('Hello World'));
    }

    public function test_snake_converts_studly_and_camel_case_like_laravel(): void
    {
        $this->assertSame('aversys_id', Str::snake('AversysId'));
        $this->assertSame('first_name', Str::snake('firstName'));
        $this->assertSame('ua_result_id', Str::snake('UaResultId'));
        $this->assertSame('already_snake', Str::snake('already_snake'));
        $this->assertSame('hello_world', Str::snake('Hello World'));
        $this->assertSame('a-b', Str::snake('aB', '-'));
    }

    public function test_highlight_wraps_each_case_insensitive_match_once_keeping_its_case(): void
    {
        $open = '<span style="font-weight: bold; color: #DA70D6;">';

        $this->assertSame(
            "{$open}Smith</span> and {$open}smith</span> and {$open}smith</span>",
            Str::highlight('Smith and smith and smith', 'smith')
        );
    }

    public function test_highlight_strips_tags_and_treats_the_term_literally(): void
    {
        $open = '<span style="font-weight: bold; color: #DA70D6;">';

        $this->assertSame("a {$open}.*</span> b", Str::highlight('<b>a</b> .* b', '.*'));
        $this->assertSame('no match', Str::highlight('<i>no match</i>', 'zzz'));
    }

    public function test_highlight_with_null_or_empty_input_returns_the_plain_haystack(): void
    {
        $this->assertSame('', Str::highlight(null, 'x'));
        $this->assertSame('Jones', Str::highlight('Jones', ''));
        $this->assertSame('Jones', Str::highlight('Jones', null));
    }
}
