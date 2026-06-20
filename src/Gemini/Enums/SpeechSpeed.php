<?php

namespace YouCast\Gemini\Gemini\Enums;

/**
 * Gemini TTS の話速プリセット
 *
 * Gemini TTS は API レベルで `speakingRate` のような構造化パラメータを持たず、
 * プロンプト先頭に自然言語のスタイル指示を入れることで話速を制御する。
 * このenumは指示文のプリセットを提供する。
 *
 * @see https://ai.google.dev/gemini-api/docs/speech-generation#controlling-style
 */
enum SpeechSpeed: string
{
    case VERY_SLOW = 'very_slow';
    case SLOW = 'slow';
    case NORMAL = 'normal';
    case FAST = 'fast';
    case VERY_FAST = 'very_fast';

    /**
     * プロンプト先頭に付与する自然言語の指示文を返す
     *
     * NORMAL は指示なしを表すため null を返す。
     */
    public function toPromptInstruction(): ?string
    {
        return match ($this) {
            self::VERY_SLOW => 'Say the following very slowly and calmly',
            self::SLOW => 'Say the following slowly',
            self::NORMAL => null,
            self::FAST => 'Say the following at a fast pace',
            self::VERY_FAST => 'Say the following very quickly',
        };
    }
}
