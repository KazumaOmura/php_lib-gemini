<?php

namespace YouCast\Gemini\Enums;

enum FileMimeType: string 
{
    case IMAGE_JPEG = 'image/jpeg';
    case IMAGE_PNG = 'image/png';
    case IMAGE_GIF = 'image/gif';
    case IMAGE_WEBP = 'image/webp';
    case IMAGE_SVG = 'image/svg+xml';
    case IMAGE_TIFF = 'image/tiff';
    case IMAGE_BMP = 'image/bmp';
    case IMAGE_ICO = 'image/x-icon';
    case APPLICATION_PDF = 'application/pdf';

    public function name(): string
    {
        return match ($this) {
            self::IMAGE_JPEG => 'JPEG',
            self::IMAGE_PNG => 'PNG',
            self::IMAGE_GIF => 'GIF',
            self::IMAGE_WEBP => 'WEBP',
            self::IMAGE_SVG => 'SVG',
            self::IMAGE_TIFF => 'TIFF',
            self::IMAGE_BMP => 'BMP',
            self::IMAGE_ICO => 'ICO',
            self::APPLICATION_PDF => 'PDF',
        };
    }
}