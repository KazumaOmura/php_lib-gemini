<?php

namespace YouCast\Gemini\Gemini;

use YouCast\Gemini\Common\Curl;
use YouCast\Gemini\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Gemini\Dto\GenerationConfigDto;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\HarmCategory;
use YouCast\Gemini\Gemini\Enums\HarmBlockThreshold;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;

/**
 * Google Gemini Batch API クライアント（非同期）
 *
 * 非同期バッチ処理用のAPIクライアント。複数のリクエストを一括処理する。
 * 48時間以内に処理され、リアルタイムAPIの50%割引で利用可能。
 *
 * @see https://ai.google.dev/gemini-api/docs/batch
 */
final class GeminiBatchClient implements BatchClientInterface
{
    private array $safety_settings = [];
    private ?GenerationConfigDto $generation_config = null;

    public function __construct(
        private string $api_key,
        private AiModel $model = AiModel::GEMINI_2_0_FLASH,
    ) {
    }

    /**
     * Safety Settingsを設定する
     *
     * @param array $safety_settings [{category: HarmCategory, threshold: HarmBlockThreshold}, ...]
     * @return self
     */
    public function setSafetySettings(array $safety_settings): self
    {
        $this->safety_settings = $safety_settings;
        return $this;
    }

    /**
     * Generation Configを設定する
     *
     * @param GenerationConfigDto $generation_config
     * @return self
     */
    public function setGenerationConfig(GenerationConfigDto $generation_config): self
    {
        $this->generation_config = $generation_config;
        return $this;
    }

    /**
     * Safety Settings配列をAPI用フォーマットに変換する
     *
     * @return array
     */
    private function buildSafetySettingsArray(): array
    {
        return array_map(fn(array $setting) => [
            'category' => $setting['category'] instanceof HarmCategory
                ? $setting['category']->value
                : $setting['category'],
            'threshold' => $setting['threshold'] instanceof HarmBlockThreshold
                ? $setting['threshold']->value
                : $setting['threshold'],
        ], $this->safety_settings);
    }

    /**
     * バッチジョブを作成
     *
     * @param array $requests リクエスト配列 [['prompt' => '...', 'inline_data' => [...]], ...]
     * @param string|null $display_name 表示名
     * @return BatchResponseDto
     * @throws GeminiApiRequestException
     */
    public function create(array $requests, ?string $display_name = null): BatchResponseDto
    {
        $safety_settings_array = $this->buildSafetySettingsArray();

        $inline_requests = [];
        foreach ($requests as $idx => $request) {
            $parts = [['text' => $request['prompt'] ?? '']];

            if (!empty($request['inline_data'])) {
                foreach ($request['inline_data'] as $data) {
                    if ($data instanceof InlineDataDto) {
                        $parts[] = $data->toGeminiPartArray();
                    }
                }
            }

            $inline_requests[] = [
                'request' => [
                    'model' => $this->model->getModelName(),
                    'contents' => [
                        [
                            'parts' => $parts,
                        ],
                    ],
                    ...(!empty($safety_settings_array) ? [
                        'safetySettings' => $safety_settings_array,
                    ] : []),
                    ...($this->generation_config !== null ? [
                        'generationConfig' => $this->generation_config->toArray(),
                    ] : []),
                ],
                'metadata' => [
                    'key' => $request['key'] ?? 'request-' . ($idx + 1),
                ],
            ];
        }

        $data = [
            'display_name' => $display_name ?? 'batch-' . date('Y-m-d-H-i-s'),
            'input_config' => [
                'requests' => [
                    'requests' => $inline_requests,
                ],
            ],
        ];

        $response = Curl::post(
            $this->model->getBatchGenerateContentUrl(),
            $this->getHeaders(),
            ['batch' => $data]
        );

        return new BatchResponseDto($response);
    }

    /**
     * バッチジョブのステータスを取得
     *
     * @param string $batch_name バッチ名（batches/xxx形式 または xxx）
     * @return BatchResponseDto
     * @throws GeminiApiRequestException
     */
    public function get(string $batch_name): BatchResponseDto
    {
        $name = $this->normalizeBatchName($batch_name);

        $response = Curl::get(
            AiModel::getBatchesUrl() . '/' . $name,
            $this->getHeaders()
        );

        return new BatchResponseDto($response);
    }

    /**
     * バッチジョブの完了を待機（ポーリング）
     *
     * @param string $batch_name バッチ名
     * @param int $max_attempts 最大試行回数
     * @param int $interval_seconds ポーリング間隔（秒）
     * @return BatchResponseDto
     * @throws GeminiApiRequestException
     */
    public function waitForCompletion(
        string $batch_name,
        int $max_attempts = 60,
        int $interval_seconds = 30,
    ): BatchResponseDto {
        for ($i = 0; $i < $max_attempts; $i++) {
            $batch = $this->get($batch_name);

            if ($batch->isCompleted()) {
                return $batch;
            }

            sleep($interval_seconds);
        }

        throw new GeminiApiRequestException(
            'バッチジョブがタイムアウトしました',
            0,
            null,
            [
                'batch_name' => $batch_name,
                'max_attempts' => $max_attempts,
                'interval_seconds' => $interval_seconds,
            ]
        );
    }

    /**
     * バッチジョブ一覧を取得
     *
     * @param int $page_size ページサイズ
     * @param string|null $page_token ページトークン
     * @return array{batches: BatchResponseDto[], next_page_token: string|null}
     * @throws GeminiApiRequestException
     */
    public function list(int $page_size = 100, ?string $page_token = null): array
    {
        $query = ['pageSize' => $page_size];
        if ($page_token !== null) {
            $query['pageToken'] = $page_token;
        }

        $response = Curl::get(
            AiModel::getBatchesUrl(),
            $this->getHeaders(),
            $query
        );

        $batches = [];
        foreach ($response['batches'] ?? [] as $batch) {
            $batches[] = new BatchResponseDto($batch);
        }

        return [
            'batches' => $batches,
            'next_page_token' => $response['nextPageToken'] ?? null,
        ];
    }

    /**
     * バッチジョブをキャンセル
     *
     * @param string $batch_name バッチ名
     * @return BatchResponseDto
     * @throws GeminiApiRequestException
     */
    public function cancel(string $batch_name): BatchResponseDto
    {
        $name = $this->normalizeBatchName($batch_name);

        $response = Curl::post(
            AiModel::getBatchesUrl() . '/' . $name . ':cancel',
            $this->getHeaders(),
            []
        );

        return new BatchResponseDto($response);
    }

    /**
     * バッチジョブを削除
     *
     * @param string $batch_name バッチ名
     * @return bool
     * @throws GeminiApiRequestException
     */
    public function delete(string $batch_name): bool
    {
        $name = $this->normalizeBatchName($batch_name);

        Curl::delete(
            AiModel::getBatchesUrl() . '/' . $name,
            $this->getHeaders()
        );

        return true;
    }

    /**
     * 結果ファイルをダウンロード
     *
     * @param string $file_name ファイル名（files/xxx形式）
     * @return array
     * @throws GeminiApiRequestException
     */
    public function downloadResponsesFile(string $file_name): array
    {
        $response = Curl::get(
            AiModel::getFileDownloadUrl($file_name),
            $this->getHeaders(),
            ['alt' => 'media']
        );

        return $response;
    }

    /**
     * バッチ名を正規化（batches/プレフィックスを除去）
     */
    private function normalizeBatchName(string $batch_name): string
    {
        return str_starts_with($batch_name, 'batches/')
            ? substr($batch_name, strlen('batches/'))
            : $batch_name;
    }

    /**
     * 共通ヘッダーを取得
     */
    private function getHeaders(): array
    {
        return [
            'x-goog-api-key: ' . $this->api_key,
            'Content-Type: application/json',
        ];
    }
}
