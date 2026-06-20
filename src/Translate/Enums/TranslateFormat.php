<?php

namespace YouCast\Gemini\Translate\Enums;

/**
 * Google Cloud Translation API のテキスト形式
 *
 * @see https://cloud.google.com/translate/docs/reference/rest/v2/translate
 */
enum TranslateFormat: string
{
    case TEXT = 'text';
    case HTML = 'html';
}
