<?php

namespace YouCast\Gemini\Tests\Unit\Gemini\Dto;

use YouCast\Gemini\Gemini\Dto\ResponseDto;
use PHPUnit\Framework\TestCase;

class ResponseDtoTest extends TestCase
{
    public function test_full_response_parsing(): void
    {
        $raw = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Hello World', 'thoughtSignature' => 'sig123'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'candidatesTokenCount' => 20,
                'totalTokenCount' => 30,
                'thoughtsTokenCount' => 5,
            ],
            'modelVersion' => 'gemini-2.0-flash',
            'responseId' => 'resp-abc',
        ];

        $dto = new ResponseDto($raw);

        $this->assertSame('Hello World', $dto->getContent());
        $this->assertSame('sig123', $dto->getThoughtSignature());
        $this->assertSame(10, $dto->getPromptTokenCount());
        $this->assertSame(20, $dto->getCandidatesTokenCount());
        $this->assertSame(30, $dto->getTotalTokenCount());
        $this->assertSame(5, $dto->getThoughtsTokenCount());
        $this->assertSame('gemini-2.0-flash', $dto->getModelVersion());
        $this->assertSame('resp-abc', $dto->getResponseId());
        $this->assertSame($raw, $dto->getRowResponse());
    }

    public function test_empty_response(): void
    {
        $dto = new ResponseDto([]);

        $this->assertNull($dto->getContent());
        $this->assertNull($dto->getThoughtSignature());
        $this->assertSame(0, $dto->getPromptTokenCount());
        $this->assertSame(0, $dto->getCandidatesTokenCount());
        $this->assertSame(0, $dto->getTotalTokenCount());
        $this->assertSame(0, $dto->getThoughtsTokenCount());
        $this->assertSame('', $dto->getModelVersion());
        $this->assertSame('', $dto->getResponseId());
    }

    public function test_partial_response_without_text(): void
    {
        $raw = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['somethingElse' => 'value'],
                        ],
                    ],
                ],
            ],
        ];

        $dto = new ResponseDto($raw);
        $this->assertNull($dto->getContent());
    }

    public function test_to_array(): void
    {
        $raw = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'test'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 100,
            ],
            'modelVersion' => 'v1',
            'responseId' => 'r1',
        ];

        $dto = new ResponseDto($raw);
        $arr = $dto->toArray();

        $this->assertSame('test', $arr['content']);
        $this->assertSame(100, $arr['total_token_count']);
        $this->assertSame('v1', $arr['model_version']);
        $this->assertSame('r1', $arr['response_id']);
        $this->assertArrayHasKey('row_response', $arr);
    }
}
