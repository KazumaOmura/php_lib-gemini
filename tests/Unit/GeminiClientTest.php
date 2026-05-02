<?php

namespace YouCast\Gemini\Common;

/**
 * Curl namespace mock functions
 * These override PHP's built-in curl_* functions within the YouCast\Gemini\Common namespace
 */

function curl_init()
{
    return \YouCast\Gemini\Tests\Unit\CurlMockState::$curlHandle ?? 'mock_ch';
}

function curl_setopt($ch, $option, $value)
{
    \YouCast\Gemini\Tests\Unit\CurlMockState::$curlOptions[$option] = $value;
    return true;
}

function curl_exec($ch)
{
    return \YouCast\Gemini\Tests\Unit\CurlMockState::$curlExecResult;
}

function curl_error($ch)
{
    return \YouCast\Gemini\Tests\Unit\CurlMockState::$curlError;
}

function curl_getinfo($ch, $option = null)
{
    if ($option === CURLINFO_HTTP_CODE) {
        return \YouCast\Gemini\Tests\Unit\CurlMockState::$httpStatusCode;
    }
    if ($option === CURLINFO_HEADER_SIZE) {
        return \YouCast\Gemini\Tests\Unit\CurlMockState::$headerSize;
    }
    return null;
}

function curl_close($ch)
{
    return true;
}

namespace YouCast\Gemini\Tests\Unit;

/**
 * Global state holder for curl mock
 */
class CurlMockState
{
    public static $curlHandle = 'mock_ch';
    public static string $curlExecResult = '';
    public static string $curlError = '';
    public static int $httpStatusCode = 200;
    public static int $headerSize = 0;
    public static array $curlOptions = [];

    public static function reset(): void
    {
        self::$curlHandle = 'mock_ch';
        self::$curlExecResult = '';
        self::$curlError = '';
        self::$httpStatusCode = 200;
        self::$headerSize = 0;
        self::$curlOptions = [];
    }

    public static function setJsonResponse(array $body, int $statusCode = 200, array $responseHeaders = []): void
    {
        $headerString = "HTTP/1.1 {$statusCode} OK\r\n";
        foreach ($responseHeaders as $name => $value) {
            $headerString .= "{$name}: {$value}\r\n";
        }
        $headerString .= "\r\n";

        $bodyString = json_encode($body);

        self::$httpStatusCode = $statusCode;
        self::$headerSize = strlen($headerString);
        self::$curlExecResult = $headerString . $bodyString;
        self::$curlError = '';
    }

    public static function setFailure(string $error = 'Connection refused'): void
    {
        self::$curlExecResult = false;
        self::$curlError = $error;
        self::$httpStatusCode = 0;
        self::$headerSize = 0;
    }
}

use YouCast\Gemini\GeminiClient;
use YouCast\Gemini\Enums\AiModel;
use YouCast\Gemini\Dto\ResponseDto;
use YouCast\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use PHPUnit\Framework\TestCase;

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

        $inlineData = (new InlineDataDto('image/png'))->setData('base64imagedata');
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

        $inlineData1 = (new InlineDataDto('image/png'))->setData('img1');
        $inlineData2 = (new InlineDataDto('image/png'))->setData('img2');

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $result = $client->request('Analyze', [$inlineData1, $inlineData2], true);

        $this->assertInstanceOf(BatchResponseDto::class, $result);
    }

    public function test_request_wraps_generic_exception(): void
    {
        // Passing invalid inline_data to trigger a LogicException
        CurlMockState::setJsonResponse([]);

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);

        $this->expectException(GeminiApiRequestException::class);
        $this->expectExceptionMessage('予期しないエラーが発生しました');

        // Pass a non-InlineDataDto to trigger LogicException
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
}
