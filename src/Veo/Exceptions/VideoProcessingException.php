<?php

namespace YouCast\Gemini\Veo\Exceptions;

use YouCast\Gemini\Exceptions\GeminiException;

/**
 * 動画処理関連の例外クラス
 */
class VideoProcessingException extends GeminiException
{
    public function __construct(string $message = "動画処理に失敗しました", int $code = 0, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
