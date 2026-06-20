<?php

namespace YouCast\Gemini\NanoBanana\Exceptions;

use YouCast\Gemini\Exceptions\GeminiException;

/**
 * 画像処理関連の例外クラス
 */
class ImageProcessingException extends GeminiException
{
    public function __construct(string $message = "画像処理に失敗しました", int $code = 0, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
