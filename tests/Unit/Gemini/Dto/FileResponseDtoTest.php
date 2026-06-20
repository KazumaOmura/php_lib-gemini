<?php

namespace YouCast\Gemini\Tests\Unit\Gemini\Dto;

use YouCast\Gemini\Gemini\Dto\FileResponseDto;
use PHPUnit\Framework\TestCase;

class FileResponseDtoTest extends TestCase
{
    private function makeResponse(array $overrides = []): array
    {
        return array_merge([
            'file' => array_merge([
                'name' => 'files/abc123',
                'displayName' => 'test.pdf',
                'mimeType' => 'application/pdf',
                'sizeBytes' => 12345,
                'createTime' => '2024-01-01T00:00:00Z',
                'updateTime' => '2024-01-01T01:00:00Z',
                'expirationTime' => '2024-01-02T00:00:00Z',
                'sha256Hash' => 'abc123hash',
                'uri' => 'https://example.com/files/abc123',
                'state' => 'ACTIVE',
            ], $overrides),
        ]);
    }

    public function test_full_parsing(): void
    {
        $dto = new FileResponseDto($this->makeResponse());

        $this->assertSame('files/abc123', $dto->getName());
        $this->assertSame('test.pdf', $dto->getDisplayName());
        $this->assertSame('application/pdf', $dto->getMimeType());
        $this->assertSame(12345, $dto->getSizeBytes());
        $this->assertSame('2024-01-01T00:00:00Z', $dto->getCreateTime());
        $this->assertSame('2024-01-01T01:00:00Z', $dto->getUpdateTime());
        $this->assertSame('2024-01-02T00:00:00Z', $dto->getExpirationTime());
        $this->assertSame('abc123hash', $dto->getSha256Hash());
        $this->assertSame('https://example.com/files/abc123', $dto->getUri());
        $this->assertSame('ACTIVE', $dto->getState());
    }

    public function test_is_active(): void
    {
        $active = new FileResponseDto($this->makeResponse(['state' => 'ACTIVE']));
        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isProcessing());

        $processing = new FileResponseDto($this->makeResponse(['state' => 'PROCESSING']));
        $this->assertFalse($processing->isActive());
        $this->assertTrue($processing->isProcessing());

        $failed = new FileResponseDto($this->makeResponse(['state' => 'FAILED']));
        $this->assertFalse($failed->isActive());
        $this->assertFalse($failed->isProcessing());
    }

    public function test_to_file_data_part(): void
    {
        $dto = new FileResponseDto($this->makeResponse());

        $expected = [
            'file_data' => [
                'mime_type' => 'application/pdf',
                'file_uri' => 'https://example.com/files/abc123',
            ],
        ];
        $this->assertSame($expected, $dto->toFileDataPart());
    }

    public function test_to_array(): void
    {
        $dto = new FileResponseDto($this->makeResponse());
        $arr = $dto->toArray();

        $this->assertSame('files/abc123', $arr['name']);
        $this->assertSame('test.pdf', $arr['display_name']);
        $this->assertSame('application/pdf', $arr['mime_type']);
        $this->assertSame(12345, $arr['size_bytes']);
        $this->assertSame('ACTIVE', $arr['state']);
        $this->assertArrayHasKey('uri', $arr);
    }

    public function test_empty_response(): void
    {
        $dto = new FileResponseDto([]);

        $this->assertSame('', $dto->getName());
        $this->assertSame('', $dto->getDisplayName());
        $this->assertSame('', $dto->getMimeType());
        $this->assertSame(0, $dto->getSizeBytes());
        $this->assertSame('', $dto->getState());
        $this->assertFalse($dto->isActive());
        $this->assertFalse($dto->isProcessing());
    }

    public function test_response_without_file_wrapper(): void
    {
        $raw = [
            'name' => 'files/direct',
            'displayName' => 'direct.jpg',
            'mimeType' => 'image/jpeg',
            'state' => 'ACTIVE',
        ];

        $dto = new FileResponseDto($raw);
        $this->assertSame('files/direct', $dto->getName());
        $this->assertSame('direct.jpg', $dto->getDisplayName());
    }
}
