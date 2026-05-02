<?php

namespace YouCast\Gemini\Tests\Unit\Enums;

use YouCast\Gemini\Enums\AiProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AiProviderTest extends TestCase
{
    public function test_name_returns_display_name(): void
    {
        $this->assertSame('Gemini', AiProvider::GEMINI->name());
    }

    public function test_from_string_valid(): void
    {
        $this->assertSame(AiProvider::GEMINI, AiProvider::fromString('gemini'));
    }

    public function test_from_string_invalid_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ai provider: openai');
        AiProvider::fromString('openai');
    }

    public function test_value(): void
    {
        $this->assertSame('gemini', AiProvider::GEMINI->value);
    }
}
