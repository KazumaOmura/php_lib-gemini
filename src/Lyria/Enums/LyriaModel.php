<?php

namespace YouCast\Gemini\Lyria\Enums;

use InvalidArgumentException;

/**
 * Lyria（音楽生成）モデル
 *
 * - lyria-3-pro-preview: 最長3分（184秒）の完成度の高い楽曲を生成
 * - lyria-3-clip-preview: 30秒のクリップを生成
 *
 * 利用可能な経路は2系統:
 *  - Generative Language API (AI Studio): x-goog-api-key 認証
 *  - Vertex AI (Agent Platform): OAuth Bearer トークン認証
 */
enum LyriaModel: string
{
    case LYRIA_3_PRO = 'lyria-3-pro-preview';
    case LYRIA_3_CLIP = 'lyria-3-clip-preview';

    private const GENERATIVE_LANGUAGE_BASE_URL = 'https://generativelanguage.googleapis.com';
    private const GENERATIVE_LANGUAGE_API_VERSION = 'v1beta';
    private const VERTEX_AI_REGIONAL_BASE_URL_TEMPLATE = 'https://%s-aiplatform.googleapis.com';
    private const VERTEX_AI_GLOBAL_BASE_URL = 'https://aiplatform.googleapis.com';
    private const VERTEX_AI_API_VERSION = 'v1';

    /**
     * モデル名（models/xxx形式）
     */
    public function getModelName(): string
    {
        return 'models/' . $this->value;
    }

    /**
     * Generative Language API (AI Studio) の generateContent URL
     */
    public function getGenerativeLanguageUrl(): string
    {
        return self::GENERATIVE_LANGUAGE_BASE_URL
            . '/' . self::GENERATIVE_LANGUAGE_API_VERSION
            . '/models/' . $this->value . ':generateContent';
    }

    /**
     * Vertex AI (Agent Platform) の generateContent URL
     *
     * @param string $project_id GCPプロジェクトID
     * @param string $location リージョン（'global' または 'us-central1' など）
     */
    public function getVertexAiUrl(string $project_id, string $location = 'global'): string
    {
        if ($project_id === '') {
            throw new InvalidArgumentException('project_id は必須です');
        }
        if ($location === '') {
            throw new InvalidArgumentException('location は必須です');
        }

        $base = $location === 'global'
            ? self::VERTEX_AI_GLOBAL_BASE_URL
            : sprintf(self::VERTEX_AI_REGIONAL_BASE_URL_TEMPLATE, $location);

        return sprintf(
            '%s/%s/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
            $base,
            self::VERTEX_AI_API_VERSION,
            $project_id,
            $location,
            $this->value
        );
    }

    public static function fromString(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Invalid lyria model: {$value}");
    }
}
