<?php

namespace YouCast\Gemini\Tests\Unit\Gemini;

use PHPUnit\Framework\TestCase;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Gemini\Dto\ResponseDto;
use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\FileMimeType;
use YouCast\Gemini\Gemini\GeminiClient;
use YouCast\Gemini\Tests\Unit\CurlMockState;

class GeminiClientTest extends TestCase
{
    protected function setUp(): void
    {
        CurlMockState::reset();
    }

    public function test_request_returns_response_dto(): void
    {
        CurlMockState::setJsonResponse([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [['text' => 'Hello from Gemini']],
                    ],
                ],
            ],
            'usageMetadata' => ['totalTokenCount' => 50],
            'modelVersion' => 'gemini-2.0-flash',
            'responseId' => 'resp-1',
        ]);

        $client = new GeminiClient('test-api-key', AiModel::GEMINI_2_0_FLASH);
        $result = $client->request('Hello');

        $this->assertInstanceOf(ResponseDto::class, $result);
        $this->assertSame('Hello from Gemini', $result->getContent());
        $this->assertSame(50, $result->getTotalTokenCount());
    }

    public function test_request_json_mode_sets_generation_config(): void
    {
        CurlMockState::setJsonResponse([
            'candidates' => [
                ['content' => ['parts' => [['text' => '{"key":"value"}']]]],
            ],
        ]);

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $client->request('Return JSON', [], false, true);

        $requestData = $client->getRequestData();
        $this->assertArrayHasKey('generationConfig', $requestData);
        $this->assertSame('application/json', $requestData['generationConfig']['responseMimeType']);
    }

    public function test_request_without_json_mode(): void
    {
        CurlMockState::setJsonResponse([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'plain text']]]],
            ],
        ]);

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $client->request('Hello', [], false, false);

        $requestData = $client->getRequestData();
        $this->assertArrayNotHasKey('generationConfig', $requestData);
    }

    public function test_request_with_inline_data(): void
    {
        CurlMockState::setJsonResponse([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'Described image']]]],
            ],
        ]);

        $inlineData = (new InlineDataDto(FileMimeType::IMAGE_PNG))->setData('base64imagedata');
        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $client->request('Describe', [$inlineData]);

        $requestData = $client->getRequestData();
        $parts = $requestData['contents'][0]['parts'];
        $this->assertSame('Describe', $parts[0]['text']);
        $this->assertArrayHasKey('inline_data', $parts[1]);
    }

    public function test_request_batch_mode(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'batches/test',
            'done' => false,
            'metadata' => ['state' => 'BATCH_STATE_PENDING'],
        ]);

        $inlineData1 = (new InlineDataDto(FileMimeType::IMAGE_PNG))->setData('img1');
        $inlineData2 = (new InlineDataDto(FileMimeType::IMAGE_PNG))->setData('img2');

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $result = $client->request('Analyze', [$inlineData1, $inlineData2], true);

        $this->assertInstanceOf(BatchResponseDto::class, $result);
    }

    public function test_request_wraps_generic_exception(): void
    {
        CurlMockState::setJsonResponse([]);

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);

        $this->expectException(GeminiApiRequestException::class);
        $this->expectExceptionMessage('予期しないエラーが発生しました');

        $client->request('test', ['not-an-inline-data-dto']);
    }

    public function test_request_rethrows_custom_exception(): void
    {
        CurlMockState::setFailure('Connection refused');

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);

        $this->expectException(GeminiApiRequestException::class);
        $client->request('test');
    }

    public function test_get_request_data(): void
    {
        CurlMockState::setJsonResponse([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'ok']]]],
            ],
        ]);

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $this->assertSame([], $client->getRequestData());

        $client->request('test');
        $this->assertNotEmpty($client->getRequestData());
        $this->assertArrayHasKey('contents', $client->getRequestData());
    }

    public function test_generate_text_omits_response_mime_type(): void
    {
        CurlMockState::setJsonResponse([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'plain text']]]],
            ],
        ]);

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $result = $client->generateText('Hello');

        $this->assertInstanceOf(ResponseDto::class, $result);
        $this->assertSame('plain text', $result->getContent());

        $requestData = $client->getRequestData();
        $this->assertArrayNotHasKey('generationConfig', $requestData);
    }

    public function test_generate_json_sets_response_mime_type(): void
    {
        CurlMockState::setJsonResponse([
            'candidates' => [
                ['content' => ['parts' => [['text' => '{"answer":42}']]]],
            ],
        ]);

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH);
        $result = $client->generateJson('Return a JSON');

        $this->assertInstanceOf(ResponseDto::class, $result);
        $this->assertSame('{"answer":42}', $result->getContent());

        $requestData = $client->getRequestData();
        $this->assertSame('application/json', $requestData['generationConfig']['responseMimeType']);
    }
}
