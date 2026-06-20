<?php

namespace YouCast\Gemini\Lyria;

use YouCast\Gemini\Common\Curl;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Lyria\Dto\LyriaResponseDto;
use YouCast\Gemini\Lyria\Enums\LyriaModel;
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;

/**
 * Google Lyria 3（音楽生成）APIのクライアント
 *
 * 2系統のエンドポイントに対応:
 *  - Generative Language API (AI Studio): forGenerativeLanguage()
 *  - Vertex AI (Agent Platform):           forVertexAi()
 */
class LyriaClient
{
    public const ENDPOINT_GENERATIVE_LANGUAGE = 'generative_language';
    public const ENDPOINT_VERTEX_AI = 'vertex_ai';

    private array $request_data = [];

    private function __construct(
        private LyriaModel $model,
        private string $endpoint_type,
        private string $credential,
        private ?string $project_id = null,
        private string $location = 'global',
    ) {
    }

    /**
     * Generative Language API (AI Studio) 経由のクライアントを生成
     *
     * @param string $api_key x-goog-api-key で送信するAPIキー
     */
    public static function forGenerativeLanguage(string $api_key, LyriaModel $model): self
    {
        if ($api_key === '') {
            throw new GeminiApiKeyException('api_key は必須です');
        }
        return new self($model, self::ENDPOINT_GENERATIVE_LANGUAGE, $api_key);
    }

    /**
     * Vertex AI (Agent Platform) 経由のクライアントを生成
     *
     * @param string $access_token Authorization: Bearer に乗せるOAuthアクセストークン
     * @param string $project_id   GCPプロジェクトID
     * @param string $location     リージョン（'global' または 'us-central1' など）
     */
    public static function forVertexAi(
        string $access_token,
        string $project_id,
        LyriaModel $model,
        string $location = 'global'
    ): self {
        if ($access_token === '') {
            throw new GeminiApiKeyException('access_token は必須です');
        }
        if ($project_id === '') {
            throw new \InvalidArgumentException('project_id は必須です');
        }
        return new self($model, self::ENDPOINT_VERTEX_AI, $access_token, $project_id, $location);
    }

    /**
     * Lyria APIを使用して音楽を生成する
     *
     * @param string $prompt 楽曲・歌詞・ジャンル等を指示するテキスト
     * @param InlineDataDto[] $inline_data 画像入力（最大10枚）など。空配列ならテキストのみ
     * @param string[] $response_modalities 受け取るモダリティ（既定は ['AUDIO', 'TEXT']）
     */
    public function request(
        string $prompt,
        array $inline_data = [],
        array $response_modalities = ['AUDIO', 'TEXT']
    ): LyriaResponseDto {
        try {
            $parts = [['text' => $prompt]];
            foreach ($inline_data as $data) {
                if (!$data instanceof InlineDataDto) {
                    throw new \LogicException('inline_dataの要素はInlineDataDtoインスタンスである必要があります');
                }
                $parts[] = $data->toGeminiPartArray();
            }

            $this->request_data = [
                'contents' => [
                    ['parts' => $parts],
                ],
                'generationConfig' => [
                    'responseModalities' => $response_modalities,
                ],
            ];

            $response = Curl::post($this->getUrl(), $this->getHeaders(), $this->request_data);

            return new LyriaResponseDto($response);
        } catch (GeminiApiKeyException | GeminiApiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeminiApiRequestException(
                '予期しないエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'prompt' => $prompt,
                    'original_error' => $e->getMessage(),
                ]
            );
        }
    }

    public function getRequestData(): array
    {
        return $this->request_data;
    }

    public function getEndpointType(): string
    {
        return $this->endpoint_type;
    }

    private function getUrl(): string
    {
        return match ($this->endpoint_type) {
            self::ENDPOINT_GENERATIVE_LANGUAGE => $this->model->getGenerativeLanguageUrl(),
            self::ENDPOINT_VERTEX_AI => $this->model->getVertexAiUrl((string) $this->project_id, $this->location),
        };
    }

    private function getHeaders(): array
    {
        return match ($this->endpoint_type) {
            self::ENDPOINT_GENERATIVE_LANGUAGE => [
                'x-goog-api-key: ' . $this->credential,
                'Content-Type: application/json',
            ],
            self::ENDPOINT_VERTEX_AI => [
                'Authorization: Bearer ' . $this->credential,
                'Content-Type: application/json',
            ],
        };
    }
}
