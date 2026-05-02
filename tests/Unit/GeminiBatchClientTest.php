<?php

namespace YouCast\Gemini\Tests\Unit;

use YouCast\Gemini\GeminiBatchClient;
use YouCast\Gemini\Enums\AiModel;
use YouCast\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use PHPUnit\Framework\TestCase;

// CurlMockState is already declared in GeminiClientTest.php and loaded via autoload

class GeminiBatchClientTest extends TestCase
{
    protected function setUp(): void
    {
        CurlMockState::reset();
    }

    public function test_create_batch_job(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'batches/new-batch',
            'done' => false,
            'metadata' => [
                'state' => 'BATCH_STATE_PENDING',
                'model' => 'models/gemini-2.0-flash',
                'displayName' => 'test-batch',
            ],
        ]);

        $client = new GeminiBatchClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $result = $client->create([
            ['prompt' => 'Question 1', 'key' => 'q1'],
            ['prompt' => 'Question 2'],
        ], 'test-batch');

        $this->assertInstanceOf(BatchResponseDto::class, $result);
        $this->assertSame('batches/new-batch', $result->getName());
    }

    public function test_get_batch_job(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'batches/abc123',
            'done' => true,
            'metadata' => [
                'state' => 'BATCH_STATE_SUCCEEDED',
            ],
        ]);

        $client = new GeminiBatchClient('test-key');
        $result = $client->get('batches/abc123');

        $this->assertInstanceOf(BatchResponseDto::class, $result);
        $this->assertTrue($result->isSucceeded());
    }

    public function test_get_batch_job_without_prefix(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'batches/abc123',
            'done' => true,
            'metadata' => ['state' => 'BATCH_STATE_SUCCEEDED'],
        ]);

        $client = new GeminiBatchClient('test-key');
        $result = $client->get('abc123');

        $this->assertInstanceOf(BatchResponseDto::class, $result);
    }

    public function test_list_batch_jobs(): void
    {
        CurlMockState::setJsonResponse([
            'batches' => [
                [
                    'name' => 'batches/b1',
                    'metadata' => ['state' => 'BATCH_STATE_SUCCEEDED'],
                ],
                [
                    'name' => 'batches/b2',
                    'metadata' => ['state' => 'BATCH_STATE_RUNNING'],
                ],
            ],
            'nextPageToken' => 'page2token',
        ]);

        $client = new GeminiBatchClient('test-key');
        $result = $client->list(50);

        $this->assertCount(2, $result['batches']);
        $this->assertInstanceOf(BatchResponseDto::class, $result['batches'][0]);
        $this->assertSame('page2token', $result['next_page_token']);
    }

    public function test_list_empty(): void
    {
        CurlMockState::setJsonResponse([]);

        $client = new GeminiBatchClient('test-key');
        $result = $client->list();

        $this->assertEmpty($result['batches']);
        $this->assertNull($result['next_page_token']);
    }

    public function test_cancel_batch_job(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'batches/abc',
            'metadata' => ['state' => 'BATCH_STATE_CANCELLED'],
        ]);

        $client = new GeminiBatchClient('test-key');
        $result = $client->cancel('batches/abc');

        $this->assertInstanceOf(BatchResponseDto::class, $result);
    }

    public function test_delete_batch_job(): void
    {
        CurlMockState::setJsonResponse([]);

        $client = new GeminiBatchClient('test-key');
        $result = $client->delete('batches/abc');

        $this->assertTrue($result);
    }

    public function test_api_error_throws_exception(): void
    {
        $headerString = "HTTP/1.1 400 Bad Request\r\n\r\n";
        $body = json_encode(['error' => ['message' => 'Invalid request']]);

        CurlMockState::$httpStatusCode = 400;
        CurlMockState::$headerSize = strlen($headerString);
        CurlMockState::$curlExecResult = $headerString . $body;

        $client = new GeminiBatchClient('test-key');

        $this->expectException(GeminiApiRequestException::class);
        $client->get('bad-batch');
    }
}
