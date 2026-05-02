<?php

namespace YouCast\Gemini\Tests\Unit\Dto;

use YouCast\Gemini\Dto\InlineDataDto;
use LogicException;
use PHPUnit\Framework\TestCase;

class InlineDataDtoTest extends TestCase
{
    public function test_set_data_fluent_interface(): void
    {
        $dto = new InlineDataDto('image/png');
        $result = $dto->setData('base64data');

        $this->assertSame($dto, $result);
        $this->assertSame('base64data', $dto->getData());
    }

    public function test_set_file_uri_fluent_interface(): void
    {
        $dto = new InlineDataDto('image/jpeg');
        $result = $dto->setFileUri('https://example.com/file');

        $this->assertSame($dto, $result);
        $this->assertSame('https://example.com/file', $dto->getFileUri());
    }

    public function test_get_mime_type(): void
    {
        $dto = new InlineDataDto('application/pdf');
        $this->assertSame('application/pdf', $dto->getMimeType());
    }

    public function test_initial_null_values(): void
    {
        $dto = new InlineDataDto('image/png');
        $this->assertNull($dto->getData());
        $this->assertNull($dto->getFileUri());
    }

    public function test_to_inline_data_part(): void
    {
        $dto = (new InlineDataDto('image/png'))->setData('abc123');

        $expected = [
            'inline_data' => [
                'mime_type' => 'image/png',
                'data' => 'abc123',
            ],
        ];
        $this->assertSame($expected, $dto->toInlineDataPart());
    }

    public function test_to_inline_data_part_throws_without_data(): void
    {
        $dto = new InlineDataDto('image/png');

        $this->expectException(LogicException::class);
        $dto->toInlineDataPart();
    }

    public function test_to_file_data_part(): void
    {
        $dto = (new InlineDataDto('application/pdf'))->setFileUri('https://example.com/file.pdf');

        $expected = [
            'file_data' => [
                'mime_type' => 'application/pdf',
                'file_uri' => 'https://example.com/file.pdf',
            ],
        ];
        $this->assertSame($expected, $dto->toFileDataPart());
    }

    public function test_to_file_data_part_throws_without_uri(): void
    {
        $dto = new InlineDataDto('image/png');

        $this->expectException(LogicException::class);
        $dto->toFileDataPart();
    }

    public function test_to_gemini_part_array_prefers_file_uri(): void
    {
        $dto = (new InlineDataDto('image/png'))
            ->setData('base64data')
            ->setFileUri('https://example.com/file');

        $result = $dto->toGeminiPartArray();
        $this->assertArrayHasKey('file_data', $result);
        $this->assertArrayNotHasKey('inline_data', $result);
    }

    public function test_to_gemini_part_array_uses_inline_data(): void
    {
        $dto = (new InlineDataDto('image/png'))->setData('base64data');

        $result = $dto->toGeminiPartArray();
        $this->assertArrayHasKey('inline_data', $result);
    }

    public function test_to_gemini_part_array_throws_without_any_data(): void
    {
        $dto = new InlineDataDto('image/png');

        $this->expectException(LogicException::class);
        $dto->toGeminiPartArray();
    }
}
