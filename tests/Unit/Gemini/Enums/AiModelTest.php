<?php

namespace YouCast\Gemini\Tests\Unit\Gemini\Enums;

use YouCast\Gemini\Gemini\Enums\AiModel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AiModelTest extends TestCase
{
    public function test_get_model_name(): void
    {
        $this->assertSame('models/gemini-2.0-flash', AiModel::GEMINI_2_0_FLASH->getModelName());
        $this->assertSame('models/gemini-2.5-pro', AiModel::GEMINI_2_5_PRO->getModelName());
        $this->assertSame('models/gemini-2.5-flash', AiModel::GEMINI_2_5_FLASH->getModelName());
        $this->assertSame('models/gemini-3-pro-preview', AiModel::GEMINI_3_PRO->getModelName());
        $this->assertSame('models/gemini-3-flash-preview', AiModel::GEMINI_3_FLASH->getModelName());
    }

    public function test_get_generate_content_url(): void
    {
        $url = AiModel::GEMINI_2_0_FLASH->getGenerateContentUrl();
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
            $url
        );
    }

    public function test_get_batch_generate_content_url(): void
    {
        $url = AiModel::GEMINI_2_5_PRO->getBatchGenerateContentUrl();
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:batchGenerateContent',
            $url
        );
    }

    public function test_get_batches_url(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/batches',
            AiModel::getBatchesUrl()
        );
    }

    public function test_get_file_upload_url(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/upload/v1beta/files',
            AiModel::getFileUploadUrl()
        );
    }

    public function test_get_files_url(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/files',
            AiModel::getFilesUrl()
        );
    }

    public function test_get_file_download_url(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/download/v1beta/files/abc123:download',
            AiModel::getFileDownloadUrl('files/abc123')
        );
    }

    public function test_from_string_valid(): void
    {
        $this->assertSame(AiModel::GEMINI_2_0_FLASH, AiModel::fromString('gemini-2.0-flash'));
        $this->assertSame(AiModel::GEMINI_2_5_PRO, AiModel::fromString('gemini-2.5-pro'));
    }

    public function test_from_string_resolves_new_flash_models(): void
    {
        $this->assertSame(AiModel::GEMINI_3_7_FLASH, AiModel::fromString('gemini-3.7-flash'));
        $this->assertSame(AiModel::GEMINI_3_6_FLASH, AiModel::fromString('gemini-3.6-flash'));
        $this->assertSame(AiModel::GEMINI_3_5_FLASH, AiModel::fromString('gemini-3.5-flash'));
        $this->assertSame(AiModel::GEMINI_3_5_FLASH_LITE, AiModel::fromString('gemini-3.5-flash-lite'));
        $this->assertSame(AiModel::GEMINI_3_1_FLASH_LITE, AiModel::fromString('gemini-3.1-flash-lite'));
    }

    public function test_new_flash_models_build_generate_content_url(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent',
            AiModel::GEMINI_3_7_FLASH->getGenerateContentUrl()
        );
    }

    public function test_new_flash_models_are_not_tts(): void
    {
        $this->assertFalse(AiModel::GEMINI_3_7_FLASH->isTts());
        $this->assertFalse(AiModel::GEMINI_3_5_FLASH_LITE->isTts());
    }

    public function test_from_string_invalid_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ai model: unknown-model');
        AiModel::fromString('unknown-model');
    }

    public function test_all_cases_have_string_values(): void
    {
        foreach (AiModel::cases() as $case) {
            $this->assertNotEmpty($case->value);
            $this->assertStringContainsString('gemini', $case->value);
        }
    }
}
