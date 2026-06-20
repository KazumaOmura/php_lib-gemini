<?php

namespace YouCast\Gemini\Tests\Unit\Gemini;

use PHPUnit\Framework\TestCase;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Gemini\Dto\AudioResponseDto;
use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\SpeechSpeed;
use YouCast\Gemini\Gemini\Enums\Voice;
use YouCast\Gemini\Gemini\GeminiClient;
use YouCast\Gemini\Tests\Unit\CurlMockState;

class GeminiClientAudioTest extends TestCase
{
    protected function setUp(): void
    {
        CurlMockState::reset();
    }

    private function setTtsResponse(): string
    {
        $audioBase64 = base64_encode('PCM_AUDIO_BYTES');
        CurlMockState::setJsonResponse([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['inlineData' => ['mimeType' => 'audio/L16;codec=pcm;rate=24000', 'data' => $audioBase64]],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => ['totalTokenCount' => 25],
            'modelVersion' => 'gemini-2.5-flash-preview-tts',
        ]);
        return $audioBase64;
    }

    public function test_generate_audio_returns_audio_response(): void
    {
        $audioBase64 = $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_0_FLASH);
        $result = $client->generateAudio('Say hello cheerfully');

        $this->assertInstanceOf(AudioResponseDto::class, $result);
        $this->assertSame($audioBase64, $result->getAudioBase64());
        $this->assertSame('PCM_AUDIO_BYTES', $result->getAudioBytes());
        $this->assertSame('audio/L16;codec=pcm;rate=24000', $result->getAudioMimeType());
        $this->assertTrue($result->hasAudio());
    }

    public function test_generate_audio_falls_back_to_default_tts_model(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH);
        $client->generateAudio('Hi');

        $url = CurlMockState::$curlOptions[CURLOPT_URL] ?? '';
        $this->assertStringContainsString('gemini-2.5-flash-preview-tts:generateContent', $url);
    }

    public function test_generate_audio_uses_constructor_tts_model_when_set(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_PRO_TTS);
        $client->generateAudio('Hi');

        $url = CurlMockState::$curlOptions[CURLOPT_URL] ?? '';
        $this->assertStringContainsString('gemini-2.5-pro-preview-tts:generateContent', $url);
    }

    public function test_generate_audio_uses_explicit_model_arg(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH);
        $client->generateAudio('Hi', Voice::KORE, AiModel::GEMINI_3_1_FLASH_TTS);

        $url = CurlMockState::$curlOptions[CURLOPT_URL] ?? '';
        $this->assertStringContainsString('gemini-3.1-flash-tts-preview:generateContent', $url);
    }

    public function test_generate_audio_sets_speech_config_with_voice(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);
        $client->generateAudio('Hi', Voice::PUCK);

        $requestData = $client->getRequestData();
        $this->assertSame(['AUDIO'], $requestData['generationConfig']['responseModalities']);
        $this->assertSame(
            'Puck',
            $requestData['generationConfig']['speechConfig']['voiceConfig']['prebuiltVoiceConfig']['voiceName']
        );
    }

    public function test_generate_audio_prepends_speed_instruction_when_speed_is_given(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);
        $client->generateAudio('吾輩は猫である', Voice::KORE, null, SpeechSpeed::SLOW);

        $requestData = $client->getRequestData();
        $sentText = $requestData['contents'][0]['parts'][0]['text'];

        $this->assertSame('Say the following slowly: 吾輩は猫である', $sentText);
    }

    public function test_generate_audio_does_not_modify_prompt_when_speed_is_normal(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);
        $client->generateAudio('Hi', Voice::KORE, null, SpeechSpeed::NORMAL);

        $requestData = $client->getRequestData();
        $this->assertSame('Hi', $requestData['contents'][0]['parts'][0]['text']);
    }

    public function test_generate_audio_does_not_modify_prompt_when_speed_is_null(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);
        $client->generateAudio('Hi', Voice::KORE);

        $requestData = $client->getRequestData();
        $this->assertSame('Hi', $requestData['contents'][0]['parts'][0]['text']);
    }

    public function test_generate_multi_speaker_audio_prepends_speed_instruction(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);
        $client->generateMultiSpeakerAudio(
            'Joe: Hi. Jane: Hello.',
            ['Joe' => Voice::KORE, 'Jane' => Voice::PUCK],
            null,
            SpeechSpeed::FAST,
        );

        $requestData = $client->getRequestData();
        $sentText = $requestData['contents'][0]['parts'][0]['text'];

        $this->assertSame('Say the following at a fast pace: Joe: Hi. Jane: Hello.', $sentText);
    }

    public function test_generate_multi_speaker_audio_builds_speaker_configs(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);
        $client->generateMultiSpeakerAudio(
            'TTS the following conversation between Joe and Jane: Joe: Hi. Jane: Hello.',
            ['Joe' => Voice::KORE, 'Jane' => Voice::PUCK],
        );

        $requestData = $client->getRequestData();
        $speakers = $requestData['generationConfig']['speechConfig']['multiSpeakerVoiceConfig']['speakerVoiceConfigs'];

        $this->assertCount(2, $speakers);
        $this->assertSame('Joe', $speakers[0]['speaker']);
        $this->assertSame('Kore', $speakers[0]['voiceConfig']['prebuiltVoiceConfig']['voiceName']);
        $this->assertSame('Jane', $speakers[1]['speaker']);
        $this->assertSame('Puck', $speakers[1]['voiceConfig']['prebuiltVoiceConfig']['voiceName']);
    }

    public function test_generate_multi_speaker_audio_requires_speakers(): void
    {
        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);

        $this->expectException(\InvalidArgumentException::class);
        $client->generateMultiSpeakerAudio('Hi', []);
    }

    public function test_generate_audio_save_to_wav_writes_header(): void
    {
        $this->setTtsResponse();

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);
        $dto = $client->generateAudio('Hi');

        $tmp = tempnam(sys_get_temp_dir(), 'tts_test_') . '.wav';
        try {
            $this->assertTrue($dto->saveAsWav($tmp));

            $bytes = file_get_contents($tmp);
            // WAV header
            $this->assertSame('RIFF', substr($bytes, 0, 4));
            $this->assertSame('WAVE', substr($bytes, 8, 4));
            $this->assertSame('fmt ', substr($bytes, 12, 4));
            // PCM body is appended after the 44-byte header
            $this->assertSame('PCM_AUDIO_BYTES', substr($bytes, 44));
        } finally {
            @unlink($tmp);
        }
    }

    public function test_generate_audio_wraps_failure(): void
    {
        CurlMockState::setFailure('Connection refused');

        $client = new GeminiClient('test-key', AiModel::GEMINI_2_5_FLASH_TTS);

        $this->expectException(GeminiApiRequestException::class);
        $client->generateAudio('Hi');
    }
}
