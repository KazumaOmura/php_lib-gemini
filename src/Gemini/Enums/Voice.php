<?php

namespace YouCast\Gemini\Gemini\Enums;

/**
 * Gemini TTS (Text-to-Speech) で利用可能な prebuilt voice
 *
 * 30種類の音声から選択できる。性格・トーンが異なるため、用途に応じて選ぶ。
 *
 * @see https://ai.google.dev/gemini-api/docs/speech-generation
 */
enum Voice: string
{
    case ZEPHYR = 'Zephyr';
    case PUCK = 'Puck';
    case CHARON = 'Charon';
    case KORE = 'Kore';
    case FENRIR = 'Fenrir';
    case LEDA = 'Leda';
    case ORUS = 'Orus';
    case AOEDE = 'Aoede';
    case CALLIRRHOE = 'Callirrhoe';
    case AUTONOE = 'Autonoe';
    case ENCELADUS = 'Enceladus';
    case IAPETUS = 'Iapetus';
    case UMBRIEL = 'Umbriel';
    case ALGIEBA = 'Algieba';
    case DESPINA = 'Despina';
    case ERINOME = 'Erinome';
    case ALGENIB = 'Algenib';
    case RASALGETHI = 'Rasalgethi';
    case LAOMEDEIA = 'Laomedeia';
    case ACHERNAR = 'Achernar';
    case ALNILAM = 'Alnilam';
    case SCHEDAR = 'Schedar';
    case GACRUX = 'Gacrux';
    case PULCHERRIMA = 'Pulcherrima';
    case ACHIRD = 'Achird';
    case ZUBENELGENUBI = 'Zubenelgenubi';
    case VINDEMIATRIX = 'Vindemiatrix';
    case SADACHBIA = 'Sadachbia';
    case SADALTAGER = 'Sadaltager';
    case SULAFAT = 'Sulafat';
}
