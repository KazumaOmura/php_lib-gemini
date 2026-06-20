<?php

namespace YouCast\Gemini\Veo\Enums;

enum VideoModel: string
{
    const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    case VEO_3_1_GENERATE_PREVIEW = 'veo-3.1-generate-preview';

    public function getApiUrl(): string
    {
        return match ($this) {
            self::VEO_3_1_GENERATE_PREVIEW => self::BASE_URL . $this->value . ':predictLongRunning',
        };
    }
}
