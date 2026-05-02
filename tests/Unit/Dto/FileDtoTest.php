<?php

namespace YouCast\Gemini\Tests\Unit\Dto;

use YouCast\Gemini\Dto\FileDto;
use YouCast\Gemini\Exceptions\GeminiFileOperationException;
use PHPUnit\Framework\TestCase;

class FileDtoTest extends TestCase
{
    public function test_from_local_file(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mpc_test_');
        file_put_contents($tmpFile, 'test content');

        try {
            $dto = FileDto::fromLocalFile($tmpFile, 'test.txt');

            $this->assertSame(FileDto::SOURCE_LOCAL_FILE, $dto->getSourceType());
            $this->assertTrue($dto->isLocalFile());
            $this->assertFalse($dto->isBinaryData());
            $this->assertFalse($dto->isUrl());
            $this->assertSame($tmpFile, $dto->getData());
            $this->assertSame($tmpFile, $dto->getFile());
            $this->assertSame('test.txt', $dto->getDisplayName());
            $this->assertSame(12, $dto->getFileSize()); // strlen('test content')
            $this->assertNotEmpty($dto->getMimeType());
        } finally {
            unlink($tmpFile);
        }
    }

    public function test_from_local_file_not_found(): void
    {
        $this->expectException(GeminiFileOperationException::class);
        $this->expectExceptionMessage('ファイルが見つかりません');

        FileDto::fromLocalFile('/nonexistent/path/file.txt', 'test');
    }

    public function test_from_binary(): void
    {
        $binary = random_bytes(100);

        $dto = FileDto::fromBinary($binary, 'image/png', 'image.png');

        $this->assertSame(FileDto::SOURCE_BINARY, $dto->getSourceType());
        $this->assertTrue($dto->isBinaryData());
        $this->assertFalse($dto->isLocalFile());
        $this->assertFalse($dto->isUrl());
        $this->assertSame($binary, $dto->getData());
        $this->assertSame('image/png', $dto->getMimeType());
        $this->assertSame('image.png', $dto->getDisplayName());
        $this->assertSame(100, $dto->getFileSize());
    }

    public function test_source_type_constants(): void
    {
        $this->assertSame('local_file', FileDto::SOURCE_LOCAL_FILE);
        $this->assertSame('binary', FileDto::SOURCE_BINARY);
        $this->assertSame('url', FileDto::SOURCE_URL);
    }
}
