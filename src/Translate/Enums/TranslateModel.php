<?php

namespace YouCast\Gemini\Translate\Enums;

/**
 * Google Cloud Translation API の翻訳モデル
 *
 * @see https://cloud.google.com/translate/docs/reference/rest/v2/translate
 */
enum TranslateModel: string
{
    case NMT = 'nmt';
    case BASE = 'base';
}
