<?php

namespace YouCast\Gemini\Tests\Unit\Lyria;

use YouCast\Gemini\Tests\Unit\CurlMockState;

use PHPUnit\Framework\TestCase;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Gemini\Enums\FileMimeType;
use YouCast\Gemini\Lyria\Dto\LyriaResponseDto;
use YouCast\Gemini\Lyria\Enums\LyriaModel;
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Lyria\LyriaClient;

// curl_* 関数のモックは GeminiClientTest.php で同じ namespace に定義済みで、
// このテストでは CurlMockState を共有して利用する。

class LyriaClientTest extends TestCase
{
    protected function setUp(): void
    {
        CurlMockState::reset();
    }

    private function setAudioResponse(): string
    {
        $audioBase64 = base64_encode('LYRIA_AUDIO_BYTES');
        CurlMockState::setJsonResponse([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Verse 1'],
                            ['inlineData' => ['mimeType' => 'audio/wav', 'data' => $audioBase64]],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => ['totalTokenCount' => 100],
            'modelVersion' => 'lyria-3-pro-preview',
            'responseId' => 'lyria-resp-1',
        ]);
        return $audioBase64;
    }

    public function test_for_generative_language_request_returns_response_dto(): void
    {
        $audioBase64 = $this->setAudioResponse();

        $client = LyriaClient::forGenerativeLanguage('test-api-key', LyriaModel::LYRIA_3_PRO);
        $result = $client->request('An atmospheric ambient track.');

        $this->assertInstanceOf(LyriaResponseDto::class, $result);
        $this->assertSame('Verse 1', $result->getText());
        $this->assertSame($audioBase64, $result->getAudioBase64());
        $this->assertSame('audio/wav', $result->getAudioMimeType());
        $this->assertSame(100, $result->getTotalTokenCount());
        $this->assertSame(LyriaClient::ENDPOINT_GENERATIVE_LANGUAGE, $client->getEndpointType());
    }

    public function test_for_generative_language_sets_api_key_header_and_url(): void
    {
        $this->setAudioResponse();

        $client = LyriaClient::forGenerativeLanguage('my-key', LyriaModel::LYRIA_3_PRO);
        $client->request('prompt');

        $headers = CurlMockState::$curlOptions[CURLOPT_HTTPHEADER] ?? [];
        $this->assertContains('x-goog-api-key: my-key', $headers);
        $this->assertContains('Content-Type: application/json', $headers);

        $url = CurlMockState::$curlOptions[CURLOPT_URL] ?? '';
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/lyria-3-pro-preview:generateContent',
            $url
        );
    }

    public function test_for_vertex_ai_sets_bearer_header_and_url(): void
    {
        $this->setAudioResponse();

        $client = LyriaClient::forVertexAi('access-tok', 'my-project', LyriaModel::LYRIA_3_CLIP, 'us-central1');
        $client->request('prompt');

        $headers = CurlMockState::$curlOptions[CURLOPT_HTTPHEADER] ?? [];
        $this->assertContains('Authorization: Bearer access-tok', $headers);
        $this->assertContains('Content-Type: application/json', $headers);

        $url = CurlMockState::$curlOptions[CURLOPT_URL] ?? '';
        $this->assertSame(
            'https://us-central1-aiplatform.googleapis.com/v1/projects/my-project/locations/us-central1/publishers/google/models/lyria-3-clip-preview:generateContent',
            $url
        );
        $this->assertSame(LyriaClient::ENDPOINT_VERTEX_AI, $client->getEndpointType());
    }

    public function test_for_vertex_ai_global_location_url(): void
    {
        $this->setAudioResponse();

        $client = LyriaClient::forVertexAi('tok', 'my-project', LyriaModel::LYRIA_3_PRO);
        $client->request('prompt');

        $url = CurlMockState::$curlOptions[CURLOPT_URL] ?? '';
        $this->assertSame(
            'https://aiplatform.googleapis.com/v1/projects/my-project/locations/global/publishers/google/models/lyria-3-pro-preview:generateContent',
            $url
        );
    }

    public function test_default_request_data_includes_audio_text_modalities(): void
    {
        $this->setAudioResponse();

        $client = LyriaClient::forGenerativeLanguage('key', LyriaModel::LYRIA_3_PRO);
        $client->request('hello');

        $requestData = $client->getRequestData();
        $this->assertSame(
            ['AUDIO', 'TEXT'],
            $requestData['generationConfig']['responseModalities']
        );
        $this->assertSame('hello', $requestData['contents'][0]['parts'][0]['text']);
    }

    public function test_custom_response_modalities(): void
    {
        $this->setAudioResponse();

        $client = LyriaClient::forGenerativeLanguage('key', LyriaModel::LYRIA_3_PRO);
        $client->request('hello', [], ['AUDIO']);

        $requestData = $client->getRequestData();
        $this->assertSame(['AUDIO'], $requestData['generationConfig']['responseModalities']);
    }

    public function test_request_with_inline_data(): void
    {
        $this->setAudioResponse();

        $image = (new InlineDataDto(FileMimeType::IMAGE_PNG))->setData('base64image');
        $client = LyriaClient::forGenerativeLanguage('key', LyriaModel::LYRIA_3_CLIP);
        $client->request('Generate based on image', [$image]);

        $parts = $client->getRequestData()['contents'][0]['parts'];
        $this->assertSame('Generate based on image', $parts[0]['text']);
        $this->assertArrayHasKey('inline_data', $parts[1]);
        $this->assertSame('image/png', $parts[1]['inline_data']['mime_type']);
    }

    public function test_for_generative_language_requires_api_key(): void
    {
        $this->expectException(GeminiApiKeyException::class);
        LyriaClient::forGenerativeLanguage('', LyriaModel::LYRIA_3_PRO);
    }

    public function test_for_vertex_ai_requires_access_token(): void
    {
        $this->expectException(GeminiApiKeyException::class);
        LyriaClient::forVertexAi('', 'my-project', LyriaModel::LYRIA_3_PRO);
    }

    public function test_for_vertex_ai_requires_project_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LyriaClient::forVertexAi('tok', '', LyriaModel::LYRIA_3_PRO);
    }

    public function test_request_wraps_generic_exception(): void
    {
        CurlMockState::setJsonResponse([]);

        $client = LyriaClient::forGenerativeLanguage('key', LyriaModel::LYRIA_3_PRO);

        $this->expectException(GeminiApiRequestException::class);
        $this->expectExceptionMessage('予期しないエラーが発生しました');

        $client->request('test', ['not-an-inline-data-dto']);
    }

    public function test_request_rethrows_custom_exception(): void
    {
        CurlMockState::setFailure('Connection refused');

        $client = LyriaClient::forGenerativeLanguage('key', LyriaModel::LYRIA_3_PRO);

        $this->expectException(GeminiApiRequestException::class);
        $client->request('test');
    }

    public function test_get_request_data_initial(): void
    {
        $client = LyriaClient::forGenerativeLanguage('key', LyriaModel::LYRIA_3_PRO);
        $this->assertSame([], $client->getRequestData());
    }
}
