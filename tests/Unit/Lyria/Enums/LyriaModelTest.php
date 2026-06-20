<?php

namespace YouCast\Gemini\Tests\Unit\Lyria\Enums;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use YouCast\Gemini\Lyria\Enums\LyriaModel;

class LyriaModelTest extends TestCase
{
    public function test_get_model_name(): void
    {
        $this->assertSame('models/lyria-3-pro-preview', LyriaModel::LYRIA_3_PRO->getModelName());
        $this->assertSame('models/lyria-3-clip-preview', LyriaModel::LYRIA_3_CLIP->getModelName());
    }

    public function test_get_generative_language_url(): void
    {
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/lyria-3-pro-preview:generateContent',
            LyriaModel::LYRIA_3_PRO->getGenerativeLanguageUrl()
        );
        $this->assertSame(
            'https://generativelanguage.googleapis.com/v1beta/models/lyria-3-clip-preview:generateContent',
            LyriaModel::LYRIA_3_CLIP->getGenerativeLanguageUrl()
        );
    }

    public function test_get_vertex_ai_url_global(): void
    {
        $this->assertSame(
            'https://aiplatform.googleapis.com/v1/projects/my-project/locations/global/publishers/google/models/lyria-3-pro-preview:generateContent',
            LyriaModel::LYRIA_3_PRO->getVertexAiUrl('my-project')
        );
    }

    public function test_get_vertex_ai_url_regional(): void
    {
        $this->assertSame(
            'https://us-central1-aiplatform.googleapis.com/v1/projects/my-project/locations/us-central1/publishers/google/models/lyria-3-clip-preview:generateContent',
            LyriaModel::LYRIA_3_CLIP->getVertexAiUrl('my-project', 'us-central1')
        );
    }

    public function test_get_vertex_ai_url_requires_project_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LyriaModel::LYRIA_3_PRO->getVertexAiUrl('');
    }

    public function test_get_vertex_ai_url_requires_location(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LyriaModel::LYRIA_3_PRO->getVertexAiUrl('my-project', '');
    }

    public function test_from_string_valid(): void
    {
        $this->assertSame(LyriaModel::LYRIA_3_PRO, LyriaModel::fromString('lyria-3-pro-preview'));
        $this->assertSame(LyriaModel::LYRIA_3_CLIP, LyriaModel::fromString('lyria-3-clip-preview'));
    }

    public function test_from_string_invalid_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid lyria model: unknown-model');
        LyriaModel::fromString('unknown-model');
    }

    public function test_all_cases_have_lyria_in_value(): void
    {
        foreach (LyriaModel::cases() as $case) {
            $this->assertNotEmpty($case->value);
            $this->assertStringContainsString('lyria', $case->value);
        }
    }
}
