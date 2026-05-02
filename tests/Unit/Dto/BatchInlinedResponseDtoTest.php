<?php

namespace YouCast\Gemini\Tests\Unit\Dto;

use YouCast\Gemini\Dto\BatchInlinedResponseDto;
use PHPUnit\Framework\TestCase;

class BatchInlinedResponseDtoTest extends TestCase
{
    public function test_basic_fields(): void
    {
        $raw = [
            'metadata' => ['key' => 'request-1'],
            'response' => [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => 'Hello']],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 42,
                ],
            ],
        ];

        $dto = new BatchInlinedResponseDto($raw);

        $this->assertSame('request-1', $dto->getKey());
        $this->assertSame('Hello', $dto->getText());
        $this->assertSame(42, $dto->getTotalTokenCount());
        $this->assertNotEmpty($dto->getCandidates());
        $this->assertSame($raw, $dto->getRawResponse());
        $this->assertSame($raw, $dto->getRowResponse());
    }

    public function test_empty_candidates(): void
    {
        $raw = [
            'metadata' => ['key' => 'req-2'],
            'response' => [
                'candidates' => [],
            ],
        ];

        $dto = new BatchInlinedResponseDto($raw);

        $this->assertNull($dto->getText());
        $this->assertSame(0, $dto->getTotalTokenCount());
    }

    public function test_missing_fields(): void
    {
        $dto = new BatchInlinedResponseDto([]);

        $this->assertNull($dto->getKey());
        $this->assertNull($dto->getText());
        $this->assertSame(0, $dto->getTotalTokenCount());
        $this->assertEmpty($dto->getCandidates());
    }

    public function test_get_parsed_json_plain(): void
    {
        $raw = [
            'response' => [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => '{"name":"test","value":123}']],
                        ],
                    ],
                ],
            ],
        ];

        $dto = new BatchInlinedResponseDto($raw);
        $parsed = $dto->getParsedJson();

        $this->assertSame(['name' => 'test', 'value' => 123], $parsed);
    }

    public function test_get_parsed_json_markdown_wrapped(): void
    {
        $jsonText = "```json\n{\"result\":true}\n```";
        $raw = [
            'response' => [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => $jsonText]],
                        ],
                    ],
                ],
            ],
        ];

        $dto = new BatchInlinedResponseDto($raw);
        $parsed = $dto->getParsedJson();

        $this->assertSame(['result' => true], $parsed);
    }

    public function test_get_parsed_json_invalid(): void
    {
        $raw = [
            'response' => [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => 'not json at all']],
                        ],
                    ],
                ],
            ],
        ];

        $dto = new BatchInlinedResponseDto($raw);
        $this->assertNull($dto->getParsedJson());
    }

    public function test_get_parsed_json_null_text(): void
    {
        $dto = new BatchInlinedResponseDto([]);
        $this->assertNull($dto->getParsedJson());
    }
}
