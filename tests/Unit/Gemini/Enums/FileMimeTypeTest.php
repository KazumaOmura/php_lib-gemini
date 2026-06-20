<?php

namespace YouCast\Gemini\Tests\Unit\Gemini\Enums;

use YouCast\Gemini\Gemini\Enums\FileMimeType;
use PHPUnit\Framework\TestCase;

class FileMimeTypeTest extends TestCase
{
    /**
     * @dataProvider mimeTypeProvider
     */
    public function test_name_and_value(FileMimeType $case, string $expectedValue, string $expectedName): void
    {
        $this->assertSame($expectedValue, $case->value);
        $this->assertSame($expectedName, $case->name());
    }

    public static function mimeTypeProvider(): array
    {
        return [
            'JPEG' => [FileMimeType::IMAGE_JPEG, 'image/jpeg', 'JPEG'],
            'PNG' => [FileMimeType::IMAGE_PNG, 'image/png', 'PNG'],
            'GIF' => [FileMimeType::IMAGE_GIF, 'image/gif', 'GIF'],
            'WEBP' => [FileMimeType::IMAGE_WEBP, 'image/webp', 'WEBP'],
            'SVG' => [FileMimeType::IMAGE_SVG, 'image/svg+xml', 'SVG'],
            'TIFF' => [FileMimeType::IMAGE_TIFF, 'image/tiff', 'TIFF'],
            'BMP' => [FileMimeType::IMAGE_BMP, 'image/bmp', 'BMP'],
            'ICO' => [FileMimeType::IMAGE_ICO, 'image/x-icon', 'ICO'],
            'PDF' => [FileMimeType::APPLICATION_PDF, 'application/pdf', 'PDF'],
        ];
    }

    public function test_all_cases_covered(): void
    {
        $this->assertCount(9, FileMimeType::cases());
    }
}
