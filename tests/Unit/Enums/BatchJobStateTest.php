<?php

namespace YouCast\Gemini\Tests\Unit\Enums;

use YouCast\Gemini\Enums\BatchJobState;
use PHPUnit\Framework\TestCase;

class BatchJobStateTest extends TestCase
{
    public function test_is_completed(): void
    {
        $this->assertTrue(BatchJobState::SUCCEEDED->isCompleted());
        $this->assertTrue(BatchJobState::FAILED->isCompleted());
        $this->assertTrue(BatchJobState::CANCELLED->isCompleted());
        $this->assertFalse(BatchJobState::PENDING->isCompleted());
        $this->assertFalse(BatchJobState::RUNNING->isCompleted());
    }

    public function test_is_processing(): void
    {
        $this->assertTrue(BatchJobState::PENDING->isProcessing());
        $this->assertTrue(BatchJobState::RUNNING->isProcessing());
        $this->assertFalse(BatchJobState::SUCCEEDED->isProcessing());
        $this->assertFalse(BatchJobState::FAILED->isProcessing());
        $this->assertFalse(BatchJobState::CANCELLED->isProcessing());
    }

    public function test_is_succeeded(): void
    {
        $this->assertTrue(BatchJobState::SUCCEEDED->isSucceeded());
        $this->assertFalse(BatchJobState::FAILED->isSucceeded());
        $this->assertFalse(BatchJobState::PENDING->isSucceeded());
    }

    public function test_is_failed(): void
    {
        $this->assertTrue(BatchJobState::FAILED->isFailed());
        $this->assertFalse(BatchJobState::SUCCEEDED->isFailed());
        $this->assertFalse(BatchJobState::PENDING->isFailed());
    }

    public function test_try_from_string_valid(): void
    {
        $this->assertSame(BatchJobState::PENDING, BatchJobState::tryFromString('BATCH_STATE_PENDING'));
        $this->assertSame(BatchJobState::RUNNING, BatchJobState::tryFromString('BATCH_STATE_RUNNING'));
        $this->assertSame(BatchJobState::SUCCEEDED, BatchJobState::tryFromString('BATCH_STATE_SUCCEEDED'));
        $this->assertSame(BatchJobState::FAILED, BatchJobState::tryFromString('BATCH_STATE_FAILED'));
        $this->assertSame(BatchJobState::CANCELLED, BatchJobState::tryFromString('BATCH_STATE_CANCELLED'));
    }

    public function test_try_from_string_null(): void
    {
        $this->assertNull(BatchJobState::tryFromString(null));
    }

    public function test_try_from_string_invalid(): void
    {
        $this->assertNull(BatchJobState::tryFromString('INVALID_STATE'));
    }

    public function test_values(): void
    {
        $this->assertSame('BATCH_STATE_PENDING', BatchJobState::PENDING->value);
        $this->assertSame('BATCH_STATE_RUNNING', BatchJobState::RUNNING->value);
        $this->assertSame('BATCH_STATE_SUCCEEDED', BatchJobState::SUCCEEDED->value);
        $this->assertSame('BATCH_STATE_FAILED', BatchJobState::FAILED->value);
        $this->assertSame('BATCH_STATE_CANCELLED', BatchJobState::CANCELLED->value);
    }
}
