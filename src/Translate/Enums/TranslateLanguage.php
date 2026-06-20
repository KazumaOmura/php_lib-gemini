<?php

namespace YouCast\Gemini\Translate\Enums;

/**
 * Google Cloud Translation API がサポートする言語コード（ISO-639-1 準拠）
 *
 * @see https://cloud.google.com/translate/docs/languages
 */
enum TranslateLanguage: string
{
    case JAPANESE = 'ja';
    case ENGLISH = 'en';
    case SPANISH = 'es';

    public function name(): string
    {
        return match ($this) {
            self::JAPANESE => 'Japanese',
            self::ENGLISH => 'English',
            self::SPANISH => 'Spanish',
        };
    }

    public static function fromString(string $value): self
    {
        return self::from($value);
    }
}
