<?php

namespace YouCast\Gemini\Tests\Unit\Dto;

use YouCast\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Dto\BatchInlinedResponseDto;
use YouCast\Gemini\Enums\BatchJobState;
use PHPUnit\Framework\TestCase;

class BatchResponseDtoTest extends TestCase
{
    private function makeRawResponse(array $overrides = []): array
    {
        return array_merge([
            'name' => 'batches/abc123',
            'done' => true,
            'metadata' => [
                'state' => 'BATCH_STATE_SUCCEEDED',
                'model' => 'models/gemini-2.0-flash',
                'displayName' => 'test-batch',
                'createTime' => '2024-01-01T00:00:00Z',
                'updateTime' => '2024-01-01T01:00:00Z',
                'batchStats' => ['totalCount' => 2, 'successCount' => 2],
                'output' => [
                    'inlinedResponses' => [
                        'inlinedResponses' => [
                            [
                                'metadata' => ['key' => 'req-1'],
                                'response' => [
                                    'candidates' => [
                                        ['content' => ['parts' => [['text' => 'Answer 1']]]],
                                    ],
                                ],
                            ],
                            [
                                'metadata' => ['key' => 'req-2'],
                                'response' => [
                                    'candidates' => [
                                        ['content' => ['parts' => [['text' => 'Answer 2']]]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $overrides);
    }

    public function test_basic_fields(): void
    {
        $dto = new BatchResponseDto($this->makeRawResponse());

        $this->assertSame('batches/abc123', $dto->getName());
        $this->assertTrue($dto->isDone());
        $this->assertSame(BatchJobState::SUCCEEDED, $dto->getState());
        $this->assertSame('BATCH_STATE_SUCCEEDED', $dto->getStateString());
        $this->assertSame('models/gemini-2.0-flash', $dto->getModel());
        $this->assertSame('test-batch', $dto->getDisplayName());
        $this->assertSame('2024-01-01T00:00:00Z', $dto->getCreateTime());
        $this->assertSame('2024-01-01T01:00:00Z', $dto->getUpdateTime());
        $this->assertSame(['totalCount' => 2, 'successCount' => 2], $dto->getBatchStats());
        $this->assertNull($dto->getError());
    }

    public function test_state_methods(): void
    {
        $succeeded = new BatchResponseDto($this->makeRawResponse());
        $this->assertTrue($succeeded->isCompleted());
        $this->assertTrue($succeeded->isSucceeded());
        $this->assertFalse($succeeded->isFailed());
        $this->assertFalse($succeeded->isProcessing());

        $processing = new BatchResponseDto($this->makeRawResponse([
            'metadata' => ['state' => 'BATCH_STATE_RUNNING'],
        ]));
        $this->assertTrue($processing->isProcessing());
        $this->assertFalse($processing->isCompleted());

        $failed = new BatchResponseDto($this->makeRawResponse([
            'metadata' => ['state' => 'BATCH_STATE_FAILED'],
        ]));
        $this->assertTrue($failed->isFailed());
        $this->assertTrue($failed->isCompleted());
    }

    public function test_inlined_responses(): void
    {
        $dto = new BatchResponseDto($this->makeRawResponse());

        $inlined = $dto->getInlinedResponses();
        $this->assertCount(2, $inlined);
        $this->assertInstanceOf(BatchInlinedResponseDto::class, $inlined[0]);
        $this->assertSame('Answer 1', $inlined[0]->getText());
        $this->assertSame('Answer 2', $inlined[1]->getText());
    }

    public function test_find_inlined_response_by_key(): void
    {
        $dto = new BatchResponseDto($this->makeRawResponse());

        $found = $dto->findInlinedResponseByKey('req-2');
        $this->assertNotNull($found);
        $this->assertSame('Answer 2', $found->getText());

        $notFound = $dto->findInlinedResponseByKey('nonexistent');
        $this->assertNull($notFound);
    }

    public function test_get_inlined_response_by_index(): void
    {
        $dto = new BatchResponseDto($this->makeRawResponse());

        $this->assertNotNull($dto->getInlinedResponseByIndex(0));
        $this->assertSame('Answer 1', $dto->getInlinedResponseByIndex(0)->getText());
        $this->assertNull($dto->getInlinedResponseByIndex(99));
    }

    public function test_to_array(): void
    {
        $dto = new BatchResponseDto($this->makeRawResponse());
        $arr = $dto->toArray();

        $this->assertSame('batches/abc123', $arr['name']);
        $this->assertSame('BATCH_STATE_SUCCEEDED', $arr['state']);
        $this->assertTrue($arr['done']);
        $this->assertArrayHasKey('model', $arr);
        $this->assertArrayHasKey('batch_stats', $arr);
    }

    public function test_empty_response(): void
    {
        $dto = new BatchResponseDto([]);

        $this->assertSame('', $dto->getName());
        $this->assertFalse($dto->isDone());
        $this->assertNull($dto->getState());
        $this->assertNull($dto->getStateString());
        $this->assertNull($dto->getModel());
        $this->assertEmpty($dto->getInlinedResponses());
    }

    public function test_inlined_responses_from_response_field(): void
    {
        $raw = [
            'name' => 'batches/xyz',
            'done' => true,
            'response' => [
                'inlinedResponses' => [
                    'inlinedResponses' => [
                        [
                            'metadata' => ['key' => 'k1'],
                            'response' => [
                                'candidates' => [
                                    ['content' => ['parts' => [['text' => 'From response']]]],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $dto = new BatchResponseDto($raw);
        $this->assertCount(1, $dto->getInlinedResponses());
        $this->assertSame('From response', $dto->getInlinedResponseByIndex(0)->getText());
    }

    public function test_get_raw_response(): void
    {
        $raw = $this->makeRawResponse();
        $dto = new BatchResponseDto($raw);
        $this->assertSame($raw, $dto->getRawResponse());
    }
}
