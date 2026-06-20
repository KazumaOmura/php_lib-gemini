<?php

namespace YouCast\Gemini\Exceptions;

/**
 * Gemini APIのインラインデータ用 ファイルMIMEタイプ例外クラス
 */
class GeminiInlineDataFileMimeTypeException extends GeminiException
{
    public function __construct(string $message = "ファイルMIMEタイプが不正です", int $code = 0, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
