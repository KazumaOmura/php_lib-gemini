<?php

namespace YouCast\Gemini\Tests\Unit\Veo;

use PHPUnit\Framework\TestCase;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Tests\Unit\CurlMockState;
use YouCast\Gemini\Veo\Dto\VeoResponseDto;
use YouCast\Gemini\Veo\Enums\VideoModel;
use YouCast\Gemini\Veo\Exceptions\VideoProcessingException;
use YouCast\Gemini\Veo\VeoClient;

class VeoClientTest extends TestCase
{
    private string $tmp_path = '';

    protected function setUp(): void
    {
        CurlMockState::reset();
        $this->tmp_path = tempnam(sys_get_temp_dir(), 'veo_test_') . '.mp4';
    }

    protected function tearDown(): void
    {
        if ($this->tmp_path !== '' && file_exists($this->tmp_path)) {
            @unlink($this->tmp_path);
        }
    }

    public function test_generate_video_completes_immediately(): void
    {
        $base64 = base64_encode('FAKE_MP4_BYTES');
        CurlMockState::setJsonResponse([
            'name' => 'operations/abc',
            'done' => true,
            'response' => [
                'predictions' => [
                    ['bytesBase64Encoded' => $base64, 'mimeType' => 'video/mp4'],
                ],
            ],
        ]);

        $client = new VeoClient('test-key', VideoModel::VEO_3_1_GENERATE_PREVIEW);
        $client->setPollingConfig(0, 1);

        $dto = $client->generateVideo('A sunset over the ocean', $this->tmp_path);

        $this->assertInstanceOf(VeoResponseDto::class, $dto);
        $this->assertSame($base64, $dto->getBase64());
        $this->assertSame('FAKE_MP4_BYTES', file_get_contents($this->tmp_path));
    }

    public function test_generate_video_throws_when_no_base64(): void
    {
        CurlMockState::setJsonResponse([
            'name' => 'operations/empty',
            'done' => true,
            'response' => ['predictions' => []],
        ]);

        $client = new VeoClient('test-key', VideoModel::VEO_3_1_GENERATE_PREVIEW);
        $client->setPollingConfig(0, 1);

        $this->expectException(VideoProcessingException::class);
        $client->generateVideo('prompt', $this->tmp_path);
    }

    public function test_generate_video_wraps_curl_failure(): void
    {
        CurlMockState::setFailure('Connection refused');

        $client = new VeoClient('test-key', VideoModel::VEO_3_1_GENERATE_PREVIEW);
        $client->setPollingConfig(0, 1);

        $this->expectException(GeminiApiRequestException::class);
        $client->generateVideo('prompt', $this->tmp_path);
    }

    public function test_get_api_key_is_masked(): void
    {
        $client = new VeoClient('abcd1234efgh5678', VideoModel::VEO_3_1_GENERATE_PREVIEW);
        $this->assertSame('abcd1234...5678', $client->getApiKey());
    }
}
