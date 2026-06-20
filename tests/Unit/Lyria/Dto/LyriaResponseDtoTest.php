<?php

namespace YouCast\Gemini\Tests\Unit\Lyria\Dto;

use PHPUnit\Framework\TestCase;
use YouCast\Gemini\Lyria\Dto\LyriaResponseDto;

class LyriaResponseDtoTest extends TestCase
{
    public function test_parses_text_and_audio_parts_camel_case(): void
    {
        $audioBase64 = base64_encode('AUDIO_BYTES');
        $dto = new LyriaResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Verse 1...'],
                            ['inlineData' => ['mimeType' => 'audio/wav', 'data' => $audioBase64]],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 12,
                'candidatesTokenCount' => 34,
                'totalTokenCount' => 46,
            ],
            'modelVersion' => 'lyria-3-pro-preview',
            'responseId' => 'resp-1',
        ]);

        $this->assertSame('Verse 1...', $dto->getText());
        $this->assertSame($audioBase64, $dto->getAudioBase64());
        $this->assertSame('AUDIO_BYTES', $dto->getAudioBytes());
        $this->assertSame('audio/wav', $dto->getAudioMimeType());
        $this->assertTrue($dto->hasAudio());
        $this->assertSame(12, $dto->getPromptTokenCount());
        $this->assertSame(34, $dto->getCandidatesTokenCount());
        $this->assertSame(46, $dto->getTotalTokenCount());
        $this->assertSame('lyria-3-pro-preview', $dto->getModelVersion());
        $this->assertSame('resp-1', $dto->getResponseId());
    }

    public function test_parses_audio_with_snake_case_keys(): void
    {
        $audioBase64 = base64_encode('SNAKE_AUDIO');
        $dto = new LyriaResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['inline_data' => ['mime_type' => 'audio/mpeg', 'data' => $audioBase64]],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame($audioBase64, $dto->getAudioBase64());
        $this->assertSame('audio/mpeg', $dto->getAudioMimeType());
        $this->assertSame('SNAKE_AUDIO', $dto->getAudioBytes());
        $this->assertNull($dto->getText());
    }

    public function test_concatenates_multiple_text_parts(): void
    {
        $dto = new LyriaResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Hello '],
                            ['text' => 'World'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('Hello World', $dto->getText());
        $this->assertFalse($dto->hasAudio());
        $this->assertNull($dto->getAudioBytes());
    }

    public function test_empty_response_defaults(): void
    {
        $dto = new LyriaResponseDto([]);
        $this->assertNull($dto->getText());
        $this->assertNull($dto->getAudioBase64());
        $this->assertNull($dto->getAudioBytes());
        $this->assertNull($dto->getAudioMimeType());
        $this->assertFalse($dto->hasAudio());
        $this->assertSame(0, $dto->getPromptTokenCount());
        $this->assertSame(0, $dto->getCandidatesTokenCount());
        $this->assertSame(0, $dto->getTotalTokenCount());
        $this->assertSame('', $dto->getModelVersion());
        $this->assertSame('', $dto->getResponseId());
    }

    public function test_save_audio_to_file(): void
    {
        $audioBase64 = base64_encode('SAVED_BYTES');
        $dto = new LyriaResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['inlineData' => ['mimeType' => 'audio/wav', 'data' => $audioBase64]],
                        ],
                    ],
                ],
            ],
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'lyria_test_');
        try {
            $this->assertTrue($dto->saveAudioTo($tmp));
            $this->assertSame('SAVED_BYTES', file_get_contents($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function test_save_audio_returns_false_when_no_audio(): void
    {
        $dto = new LyriaResponseDto([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'no audio']]]],
            ],
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'lyria_test_');
        try {
            $this->assertFalse($dto->saveAudioTo($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function test_to_array_includes_all_fields(): void
    {
        $dto = new LyriaResponseDto([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'desc'],
                            ['inlineData' => ['mimeType' => 'audio/wav', 'data' => 'AAAA']],
                        ],
                    ],
                ],
            ],
            'modelVersion' => 'lyria-3-pro-preview',
        ]);

        $array = $dto->toArray();
        $this->assertSame('desc', $array['text']);
        $this->assertSame('AAAA', $array['audio_base64']);
        $this->assertSame('audio/wav', $array['audio_mime_type']);
        $this->assertTrue($array['has_audio']);
        $this->assertSame('lyria-3-pro-preview', $array['model_version']);
        $this->assertArrayHasKey('row_response', $array);
    }
}
