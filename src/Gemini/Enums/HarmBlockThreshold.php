<?php

namespace YouCast\Gemini\Gemini\Enums;

enum HarmBlockThreshold: string
{
    case BLOCK_NONE = 'BLOCK_NONE';
    case BLOCK_ONLY_HIGH = 'BLOCK_ONLY_HIGH';
    case BLOCK_MEDIUM_AND_ABOVE = 'BLOCK_MEDIUM_AND_ABOVE';
    case BLOCK_LOW_AND_ABOVE = 'BLOCK_LOW_AND_ABOVE';
    case OFF = 'OFF';
}
