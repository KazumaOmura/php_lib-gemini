<?php

namespace YouCast\Gemini\Tests\Unit\Gemini\Dto;

use PHPUnit\Framework\TestCase;
use YouCast\Gemini\Gemini\Dto\AudioResponseDto;

class AudioResponseDtoTest extends TestCase
{
    public function test_parses_audio_part_camel_case(): void
    {
        $audioBase64 = base64_encode('PCM_BYTES');
        $dto = new AudioResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['inlineData' => ['mimeType' => 'audio/L16;codec=pcm;rate=24000', 'data' => $audioBase64]],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'candidatesTokenCount' => 5,
                'totalTokenCount' => 15,
            ],
            'modelVersion' => 'gemini-2.5-flash-preview-tts',
            'responseId' => 'tts-1',
        ]);

        $this->assertSame($audioBase64, $dto->getAudioBase64());
        $this->assertSame('PCM_BYTES', $dto->getAudioBytes());
        $this->assertSame('audio/L16;codec=pcm;rate=24000', $dto->getAudioMimeType());
        $this->assertTrue($dto->hasAudio());
        $this->assertSame(10, $dto->getPromptTokenCount());
        $this->assertSame(5, $dto->getCandidatesTokenCount());
        $this->assertSame(15, $dto->getTotalTokenCount());
        $this->assertSame('gemini-2.5-flash-preview-tts', $dto->getModelVersion());
        $this->assertSame('tts-1', $dto->getResponseId());
    }

    public function test_parses_audio_part_snake_case(): void
    {
        $audioBase64 = base64_encode('SNAKE');
        $dto = new AudioResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['inline_data' => ['mime_type' => 'audio/pcm', 'data' => $audioBase64]],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('SNAKE', $dto->getAudioBytes());
        $this->assertSame('audio/pcm', $dto->getAudioMimeType());
    }

    public function test_empty_response_defaults(): void
    {
        $dto = new AudioResponseDto([]);
        $this->assertNull($dto->getAudioBase64());
        $this->assertNull($dto->getAudioBytes());
        $this->assertNull($dto->getAudioMimeType());
        $this->assertFalse($dto->hasAudio());
        $this->assertSame(0, $dto->getTotalTokenCount());
    }

    public function test_save_audio_to_writes_raw_pcm(): void
    {
        $dto = new AudioResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['inlineData' => ['mimeType' => 'audio/pcm', 'data' => base64_encode('RAW')]],
                        ],
                    ],
                ],
            ],
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'audio_test_');
        try {
            $this->assertTrue($dto->saveAudioTo($tmp));
            $this->assertSame('RAW', file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function test_save_as_wav_uses_mime_sample_rate(): void
    {
        $dto = new AudioResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['inlineData' => ['mimeType' => 'audio/L16;codec=pcm;rate=48000', 'data' => base64_encode('ABCD')]],
                        ],
                    ],
                ],
            ],
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'audio_test_') . '.wav';
        try {
            $this->assertTrue($dto->saveAsWav($tmp));

            $bytes = file_get_contents($tmp);
            $this->assertSame('RIFF', substr($bytes, 0, 4));
            $this->assertSame('WAVE', substr($bytes, 8, 4));

            // sample rate at offset 24-27 (little-endian uint32)
            $sample_rate = unpack('V', substr($bytes, 24, 4))[1];
            $this->assertSame(48000, $sample_rate);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_save_as_wav_returns_false_when_no_audio(): void
    {
        $dto = new AudioResponseDto([]);
        $tmp = tempnam(sys_get_temp_dir(), 'audio_test_');
        try {
            $this->assertFalse($dto->saveAsWav($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function test_to_array_includes_all_fields(): void
    {
        $dto = new AudioResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['inlineData' => ['mimeType' => 'audio/pcm', 'data' => 'AAAA']],
                        ],
                    ],
                ],
            ],
            'modelVersion' => 'gemini-2.5-flash-preview-tts',
        ]);

        $array = $dto->toArray();
        $this->assertSame('AAAA', $array['audio_base64']);
        $this->assertSame('audio/pcm', $array['audio_mime_type']);
        $this->assertTrue($array['has_audio']);
        $this->assertSame('gemini-2.5-flash-preview-tts', $array['model_version']);
        $this->assertArrayHasKey('row_response', $array);
    }
}
