<?php

namespace YouCast\Gemini\Tests\Unit\NanoBanana;

use PHPUnit\Framework\TestCase;
use YouCast\Gemini\NanoBanana\Builders\PhotographyPromptBuilder;
use YouCast\Gemini\NanoBanana\Builders\StickerPromptBuilder;
use YouCast\Gemini\NanoBanana\Enums\ImageModel;
use YouCast\Gemini\NanoBanana\Exceptions\ImageProcessingException;

/**
 * NanoBanana の構造・クラス解決を確認するスモークテスト。
 *
 * HTTPコールを伴う統合テストは Illuminate\Support\Facades\Http のテストハーネス整備が
 * 別途必要なため、ここではクラスのオートロードとプロンプトビルダーの組み立てのみ検証する。
 */
class NanoBananaSmokeTest extends TestCase
{
    public function test_image_model_returns_correct_api_url(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent',
            ImageModel::GEMINI_2_5_FLASH_IMAGE->getApiUrl()
        );

        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:generateContent',
            ImageModel::GEMINI_3_PRO_IMAGE_PREVIEW->getApiUrl()
        );

        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-image-preview:generateContent',
            ImageModel::GEMINI_3_1_FLASH_IMAGE_PREVIEW->getApiUrl()
        );
    }

    public function test_image_model_batch_url(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-pro-image-preview:batchGenerateContent',
            ImageModel::GEMINI_3_PRO_IMAGE_PREVIEW->getApiUrl(true)
        );
    }

    public function test_photography_prompt_builder_builds_string(): void
    {
        $builder = new PhotographyPromptBuilder();
        $prompt = $builder->setSubject('a cat')->build();

        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
        $this->assertStringContainsString('cat', $prompt);
    }

    public function test_sticker_prompt_builder_builds_string(): void
    {
        $builder = new StickerPromptBuilder();
        $prompt = $builder->setSubject('pikachu')->build();

        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
        $this->assertStringContainsString('pikachu', $prompt);
    }

    public function test_image_processing_exception_extends_base(): void
    {
        $exception = new ImageProcessingException('test', 0, null, ['k' => 'v']);

        $this->assertInstanceOf(\YouCast\Gemini\Exceptions\GeminiException::class, $exception);
        $this->assertSame(['k' => 'v'], $exception->getContext());
    }
}
