<?php

namespace YouCast\Gemini\Exceptions;

/**
 * ファイル操作関連の例外クラス
 */
class GeminiFileOperationException extends GeminiException
{
    public function __construct(string $message = "ファイル操作に失敗しました", int $code = 0, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}