<?php

namespace YouCast\Gemini\Tests\Unit\Exceptions;

use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Exceptions\GeminiException;
use YouCast\Gemini\Exceptions\GeminiFileOperationException;
use PHPUnit\Framework\TestCase;

class GeminiExceptionTest extends TestCase
{
    public function test_gemini_exception(): void
    {
        $context = ['key' => 'value'];
        $e = new GeminiException('test error', 42, null, $context);

        $this->assertSame('test error', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($context, $e->getContext());
        $this->assertInstanceOf(\Exception::class, $e);
    }

    public function test_gemini_exception_set_context(): void
    {
        $e = new GeminiException('test');
        $this->assertSame([], $e->getContext());

        $e->setContext(['new' => 'context']);
        $this->assertSame(['new' => 'context'], $e->getContext());
    }

    public function test_gemini_exception_default_values(): void
    {
        $e = new GeminiException();
        $this->assertSame('', $e->getMessage());
        $this->assertSame(0, $e->getCode());
        $this->assertNull($e->getPrevious());
        $this->assertSame([], $e->getContext());
    }

    public function test_api_key_exception(): void
    {
        $e = new GeminiApiKeyException();
        $this->assertSame('APIキーが設定されていません', $e->getMessage());
        $this->assertInstanceOf(GeminiException::class, $e);
    }

    public function test_api_key_exception_custom_message(): void
    {
        $e = new GeminiApiKeyException('Custom key error', 1, null, ['api' => 'gemini']);
        $this->assertSame('Custom key error', $e->getMessage());
        $this->assertSame(1, $e->getCode());
        $this->assertSame(['api' => 'gemini'], $e->getContext());
    }

    public function test_api_request_exception(): void
    {
        $e = new GeminiApiRequestException();
        $this->assertSame('APIリクエストが失敗しました', $e->getMessage());
        $this->assertInstanceOf(GeminiException::class, $e);
    }

    public function test_api_request_exception_with_previous(): void
    {
        $previous = new \RuntimeException('original');
        $e = new GeminiApiRequestException('wrapped', 500, $previous);
        $this->assertSame($previous, $e->getPrevious());
    }

    public function test_file_operation_exception(): void
    {
        $e = new GeminiFileOperationException();
        $this->assertSame('ファイル操作に失敗しました', $e->getMessage());
        $this->assertInstanceOf(GeminiException::class, $e);
    }

    public function test_file_operation_exception_with_context(): void
    {
        $e = new GeminiFileOperationException('upload failed', 0, null, ['file' => 'test.pdf']);
        $this->assertSame('upload failed', $e->getMessage());
        $this->assertSame(['file' => 'test.pdf'], $e->getContext());
    }

    public function test_inheritance_chain(): void
    {
        $this->assertInstanceOf(\Exception::class, new GeminiException());
        $this->assertInstanceOf(GeminiException::class, new GeminiApiKeyException());
        $this->assertInstanceOf(GeminiException::class, new GeminiApiRequestException());
        $this->assertInstanceOf(GeminiException::class, new GeminiFileOperationException());
    }
}
