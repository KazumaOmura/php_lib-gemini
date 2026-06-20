<?php

namespace YouCast\Gemini\Gemini\Enums;

/**
 * Gemini TTS の話速プリセット（9段階）
 *
 * Gemini TTS は API レベルで `speakingRate` のような構造化パラメータを持たず、
 * プロンプト先頭に自然言語のスタイル指示を入れることで話速を制御する。
 * このenumは指示文のプリセットを提供する。
 *
 * 遅い順:
 *  VERY_SLOW → QUITE_SLOW → SLOW → SLIGHTLY_SLOW → NORMAL
 *  → SLIGHTLY_FAST → FAST → QUITE_FAST → VERY_FAST
 *
 * @see https://ai.google.dev/gemini-api/docs/speech-generation#controlling-style
 */
enum SpeechSpeed: string
{
    /** とてもゆっくり・落ち着いた口調 */
    case VERY_SLOW = 'very_slow';
    /** かなりゆっくり */
    case QUITE_SLOW = 'quite_slow';
    /** ゆっくり */
    case SLOW = 'slow';
    /** 普通よりやや遅め */
    case SLIGHTLY_SLOW = 'slightly_slow';
    /** 標準（指示なし） */
    case NORMAL = 'normal';
    /** 普通よりやや速め */
    case SLIGHTLY_FAST = 'slightly_fast';
    /** 速い */
    case FAST = 'fast';
    /** かなり速い */
    case QUITE_FAST = 'quite_fast';
    /** とても速い・早口 */
    case VERY_FAST = 'very_fast';

    /**
     * プロンプト先頭に付与する自然言語の指示文を返す
     *
     * NORMAL は指示なしを表すため null を返す。
     */
    public function toPromptInstruction(): ?string
    {
        return match ($this) {
            // とてもゆっくり・落ち着いた口調で
            self::VERY_SLOW => 'Say the following very slowly and calmly',
            // かなりゆっくり
            self::QUITE_SLOW => 'Say the following quite slowly',
            // ゆっくり
            self::SLOW => 'Say the following slowly',
            // 普通よりやや遅め
            self::SLIGHTLY_SLOW => 'Say the following slightly slower than normal',
            // 標準（指示なし）
            self::NORMAL => null,
            // 普通よりやや速め
            self::SLIGHTLY_FAST => 'Say the following slightly faster than normal',
            // 速いペースで
            self::FAST => 'Say the following at a fast pace',
            // かなり速く
            self::QUITE_FAST => 'Say the following quite quickly',
            // とても速く・早口で
            self::VERY_FAST => 'Say the following very quickly',
        };
    }
}
