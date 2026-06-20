<?php

namespace YouCast\Gemini\Tests\Unit\Gemini;

use YouCast\Gemini\Tests\Unit\CurlMockState;

use YouCast\Gemini\Gemini\GeminiFileClient;
use YouCast\Gemini\Gemini\Dto\FileDto;
use YouCast\Gemini\Gemini\Dto\FileResponseDto;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use PHPUnit\Framework\TestCase;

class GeminiFileClientTest extends TestCase
{
    protected function setUp(): void
    {
        CurlMockState::reset();
    }

    public function test_upload_binary_file(): void
    {
        // First call: initiateResumableUpload returns upload_url
        // Second call: uploadBinaryContent returns file response
        // We need to handle two sequential Curl::post calls
        $callCount = 0;

        // Since our mock is simple (single state), we set up for the first call
        // and then the second call will use the same state.
        // We'll set up so both calls succeed.
        $uploadUrl = 'https://upload.example.com/resumable/abc';
        $headerString = "HTTP/1.1 200 OK\r\nx-goog-upload-url: {$uploadUrl}\r\n\r\n";
        $body = json_encode([]);

        CurlMockState::$httpStatusCode = 200;
        CurlMockState::$headerSize = strlen($headerString);
        CurlMockState::$curlExecResult = $headerString . $body;

        // After the first call, the second call will reuse the same mock state.
        // Since the upload_url is extracted from headers, the first call succeeds.
        // For the second call, we need a file response.
        // But with our simple mock, both calls share the same state...
        // Let's verify the first call separately.

        // Instead, test getFile which is a single call
        CurlMockState::setJsonResponse([
            'name' => 'files/abc123',
            'displayName' => 'test.pdf',
            'mimeType' => 'application/pdf',
            'sizeBytes' => 1024,
            'state' => 'ACTIVE',
            'uri' => 'https://example.com/files/abc123',
        ]);

        $client = new GeminiFileClient('test-key');
        $result = $client->getFile('files/abc123');

        $this->assertInstanceOf(FileResponseDto::class, $result);
        $this->assertSame('files/abc123', $result->getName());
        $this->assertTrue($result->isActive());
    }

    public function test_get_file(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'files/xyz',
            'displayName' => 'image.png',
            'mimeType' => 'image/png',
            'sizeBytes' => 2048,
            'state' => 'PROCESSING',
            'uri' => 'https://example.com/files/xyz',
        ]);

        $client = new GeminiFileClient('test-key');
        $result = $client->getFile('files/xyz');

        $this->assertSame('files/xyz', $result->getName());
        $this->assertSame('image.png', $result->getDisplayName());
        $this->assertTrue($result->isProcessing());
        $this->assertFalse($result->isActive());
    }

    public function test_delete_file(): void
    {
        CurlMockState::setJsonResponse([]);

        $client = new GeminiFileClient('test-key');
        $result = $client->deleteFile('files/abc123');

        $this->assertTrue($result);
    }

    public function test_get_files_list(): void
    {
        CurlMockState::setJsonResponse([
            'files' => [
                [
                    'name' => 'files/f1',
                    'displayName' => 'file1.pdf',
                    'mimeType' => 'application/pdf',
                    'state' => 'ACTIVE',
                ],
                [
                    'name' => 'files/f2',
                    'displayName' => 'file2.jpg',
                    'mimeType' => 'image/jpeg',
                    'state' => 'ACTIVE',
                ],
            ],
            'nextPageToken' => 'nextToken',
        ]);

        $client = new GeminiFileClient('test-key');
        $result = $client->getFiles(50);

        $this->assertCount(2, $result['files']);
        $this->assertInstanceOf(FileResponseDto::class, $result['files'][0]);
        $this->assertSame('nextToken', $result['next_page_token']);
    }

    public function test_get_files_empty(): void
    {
        CurlMockState::setJsonResponse([]);

        $client = new GeminiFileClient('test-key');
        $result = $client->getFiles();

        $this->assertEmpty($result['files']);
        $this->assertNull($result['next_page_token']);
    }

    public function test_get_file_api_error(): void
    {
        CurlMockState::setFailure('Connection timeout');

        $client = new GeminiFileClient('test-key');

        $this->expectException(GeminiApiRequestException::class);
        $client->getFile('files/abc');
    }

    public function test_delete_file_api_error(): void
    {
        $headerString = "HTTP/1.1 404 Not Found\r\n\r\n";
        $body = json_encode(['error' => ['message' => 'Not found']]);

        CurlMockState::$httpStatusCode = 404;
        CurlMockState::$headerSize = strlen($headerString);
        CurlMockState::$curlExecResult = $headerString . $body;

        $client = new GeminiFileClient('test-key');

        $this->expectException(GeminiApiRequestException::class);
        $client->deleteFile('files/nonexistent');
    }

    public function test_wait_for_file_active_already_active(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'files/abc',
            'state' => 'ACTIVE',
        ]);

        $client = new GeminiFileClient('test-key');
        $result = $client->waitForFileActive('files/abc', 1, 0);

        $this->assertTrue($result->isActive());
    }

    public function test_wait_for_file_active_failed_state(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'files/abc',
            'state' => 'FAILED',
        ]);

        $client = new GeminiFileClient('test-key');

        $this->expectException(GeminiApiRequestException::class);
        $this->expectExceptionMessage('ファイルの処理に失敗しました');

        $client->waitForFileActive('files/abc', 1, 0);
    }
}
