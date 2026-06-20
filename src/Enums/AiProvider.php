<?php

namespace YouCast\Gemini\Enums;

use InvalidArgumentException;

enum AiProvider: string
{
    case GEMINI = 'gemini';
    case LYRIA = 'lyria';
    case TRANSLATE = 'translate';
    case NANOBANANA = 'nanobanana';
    case VEO = 'veo';

    public function name(): string
    {
        return match ($this) {
            self::GEMINI => 'Gemini',
            self::LYRIA => 'Lyria',
            self::TRANSLATE => 'Google Translate',
            self::NANOBANANA => 'Nano Banana',
            self::VEO => 'Veo',
        };
    }

    public static function fromString(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Invalid ai provider: {$value}");
    }
}
