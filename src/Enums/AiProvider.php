<?php

namespace YouCast\Gemini\Enums;

use InvalidArgumentException;

enum AiProvider: string 
{
    case GEMINI = 'gemini';

    public function name(): string
    {
        return match ($this) {
            self::GEMINI => 'Gemini',
        };
    }

    public static function fromString(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
    
        throw new InvalidArgumentException("Invalid ai provider: {$value}");
    }
}