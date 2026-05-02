<?php

namespace YouCast\Gemini\Exceptions;

/**
 * APIリクエスト関連の例外クラス
 */
class GeminiApiRequestException extends GeminiException
{
    public function __construct(string $message = "APIリクエストが失敗しました", int $code = 0, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
