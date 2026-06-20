<?php

namespace YouCast\Gemini\Gemini\Enums;

/**
 * Gemini Thinking機能のレベル設定
 *
 * @see https://ai.google.dev/gemini-api/docs/thinking
 */
enum ThinkingLevel: string
{
    case NONE = 'NONE';
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
}
