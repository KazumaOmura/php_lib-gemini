<?php

namespace YouCast\Gemini\Translate\Exceptions;

/**
 * Google Cloud Translation API のAPIキー関連例外クラス
 */
class TranslateApiKeyException extends TranslateException
{
    public function __construct(string $message = "APIキーが不正です", int $code = 0, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
