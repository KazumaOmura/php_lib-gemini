<?php

namespace YouCast\Gemini\Translate\Exceptions;

/**
 * Google Cloud Translation API のリクエスト関連例外クラス
 */
class TranslateApiRequestException extends TranslateException
{
    public function __construct(string $message = "APIリクエストが失敗しました", int $code = 0, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
