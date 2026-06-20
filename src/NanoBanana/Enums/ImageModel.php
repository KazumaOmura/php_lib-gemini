<?php

namespace YouCast\Gemini\NanoBanana\Enums;

/**
 * Nano Banana / Nano Banana Pro 画像生成モデル
 */
enum ImageModel: string
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    case GEMINI_2_5_FLASH_IMAGE = 'gemini-2.5-flash-image';
    case GEMINI_3_PRO_IMAGE_PREVIEW = 'gemini-3-pro-image-preview';
    case GEMINI_3_1_FLASH_IMAGE_PREVIEW = 'gemini-3.1-flash-image-preview';

    public function getApiUrl(bool $is_batch = false): string
    {
        $suffix = $is_batch ? 'batchGenerateContent' : 'generateContent';

        return self::BASE_URL . $this->value . ':' . $suffix;
    }
}
