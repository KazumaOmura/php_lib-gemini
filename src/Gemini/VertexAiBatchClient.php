<?php

namespace YouCast\Gemini\Gemini;

use YouCast\Gemini\Common\Curl;
use YouCast\Gemini\Common\VertexAiAuth;
use YouCast\Gemini\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Gemini\Dto\GenerationConfigDto;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\HarmCategory;
use YouCast\Gemini\Gemini\Enums\HarmBlockThreshold;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;

/**
 * Vertex AI Batch Prediction クライアント
 *
 * Google Cloud Vertex AI の batchPredictionJobs API を使用したバッチ処理クライアント。
 * GCS (Cloud Storage) を入出力に使用する。
 *
 * @see https://cloud.google.com/vertex-ai/generative-ai/docs/model-reference/batch-prediction-api
 */
final class VertexAiBatchClient implements BatchClientInterface
{
    private array $safety_settings = [];
    private ?GenerationConfigDto $generation_config = null;
    private VertexAiAuth $auth;

    /**
     * @param string $service_account_json_path サービスアカウントJSONキーファイルのパス
     * @param string $project_id GCPプロジェクトID
     * @param string $location リージョン（例: us-central1）
     * @param string $gcs_bucket GCSバケット名（gs://プレフィックスなし）
     * @param AiModel $model AIモデル
     */
    public function __construct(
        private string $service_account_json_path,
        private string $project_id,
        private string $location,
        private string $gcs_bucket,
        private AiModel $model = AiModel::GEMINI_2_0_FLASH,
    ) {
        $this->auth = new VertexAiAuth($service_account_json_path);
    }

    /**
     * @inheritDoc
     */
    public function setSafetySettings(array $safety_settings): self
    {
        $this->safety_settings = $safety_settings;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setGenerationConfig(GenerationConfigDto $generation_config): self
    {
        $this->generation_config = $generation_config;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * 1. リクエストをJSONL形式に変換しGCSにアップロード
     * 2. batchPredictionJobを作成
     * 3. レスポンスをBatchResponseDto形式に変換して返却
     */
    public function create(array $requests, ?string $display_name = null): BatchResponseDto
    {
        $display_name = $display_name ?? 'batch-' . date('Y-m-d-H-i-s');
        $input_gcs_path = 'ai-screening/input/' . $display_name . '.jsonl';
        $output_gcs_prefix = 'ai-screening/output/' . $display_name . '/';

        // 1. JSONL形式のリクエストデータを構築
        $jsonl_content = $this->buildJsonlContent($requests);

        // 2. GCSにアップロード
        $this->uploadToGcs($input_gcs_path, $jsonl_content);

        // 3. batchPredictionJobを作成
        $job_data = [
            'displayName' => $display_name,
            'model' => 'publishers/google/models/' . $this->model->value,
            'inputConfig' => [
                'instancesFormat' => 'jsonl',
                'gcsSource' => [
                    'uris' => ['gs://' . $this->gcs_bucket . '/' . $input_gcs_path],
                ],
            ],
            'outputConfig' => [
                'predictionsFormat' => 'jsonl',
                'gcsDestination' => [
                    'outputUriPrefix' => 'gs://' . $this->gcs_bucket . '/' . $output_gcs_prefix,
                ],
            ],
        ];

        $response = Curl::post(
            $this->getBatchJobsUrl(),
            $this->getHeaders(),
            $job_data
        );

        // Vertex AIレスポンスをBatchResponseDto互換形式に変換
        return new BatchResponseDto($this->transformToGeminiFormat($response));
    }

    /**
     * @inheritDoc
     */
    public function get(string $batch_name): BatchResponseDto
    {
        $job_id = $this->extractJobId($batch_name);

        $response = Curl::get(
            $this->getBatchJobsUrl() . '/' . $job_id,
            $this->getHeaders()
        );

        $gemini_format = $this->transformToGeminiFormat($response);

        // 完了済みの場合、GCSから結果を取得してインライン化
        if ($this->isJobCompleted($response)) {
            $inlined_responses = $this->fetchResultsFromGcs($response);
            if (!empty($inlined_responses)) {
                $gemini_format['response'] = [
                    'inlinedResponses' => [
                        'inlinedResponses' => $inlined_responses,
                    ],
                ];
                $gemini_format['metadata']['output'] = [
                    'inlinedResponses' => [
                        'inlinedResponses' => $inlined_responses,
                    ],
                ];
            }
        }

        return new BatchResponseDto($gemini_format);
    }

    /**
     * @inheritDoc
     */
    public function list(int $page_size = 100, ?string $page_token = null): array
    {
        $query = [
            'pageSize' => $page_size,
            'filter' => 'display_name_prefix="batch-" OR display_name_prefix="episode-" OR display_name_prefix="title-"',
        ];
        if ($page_token !== null) {
            $query['pageToken'] = $page_token;
        }

        $response = Curl::get(
            $this->getBatchJobsUrl(),
            $this->getHeaders(),
            $query
        );

        $batches = [];
        foreach ($response['batchPredictionJobs'] ?? [] as $job) {
            $batches[] = new BatchResponseDto($this->transformToGeminiFormat($job));
        }

        return [
            'batches' => $batches,
            'next_page_token' => $response['nextPageToken'] ?? null,
        ];
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $batch_name): BatchResponseDto
    {
        $job_id = $this->extractJobId($batch_name);

        Curl::post(
            $this->getBatchJobsUrl() . '/' . $job_id . ':cancel',
            $this->getHeaders(),
            []
        );

        // キャンセル後のステータスを取得して返す
        return $this->get($batch_name);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $batch_name): bool
    {
        $job_id = $this->extractJobId($batch_name);

        Curl::delete(
            $this->getBatchJobsUrl() . '/' . $job_id,
            $this->getHeaders()
        );

        return true;
    }

    /**
     * リクエスト配列をJSONL形式に変換
     */
    private function buildJsonlContent(array $requests): string
    {
        $safety_settings_array = $this->buildSafetySettingsArray();
        $lines = [];

        foreach ($requests as $request) {
            $parts = [['text' => $request['prompt'] ?? '']];

            if (!empty($request['inline_data'])) {
                foreach ($request['inline_data'] as $data) {
                    if ($data instanceof InlineDataDto) {
                        $parts[] = $data->toGeminiPartArray();
                    }
                }
            }

            $line = [
                'request' => [
                    'contents' => [
                        [
                            'role' => 'user',
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
            ];

            // メタデータとしてキーを保持（結果照合用）
            if (!empty($request['key'])) {
                $line['metadata'] = json_encode(['key' => $request['key']]);
            }

            $lines[] = json_encode($line, JSON_UNESCAPED_UNICODE);
        }

        return implode("\n", $lines);
    }

    /**
     * Safety Settings配列をAPI用フォーマットに変換
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
     * Vertex AIレスポンスをGemini BatchResponseDto互換形式に変換
     */
    private function transformToGeminiFormat(array $vertex_response): array
    {
        $state = $vertex_response['state'] ?? null;
        $is_done = in_array($state, [
            'JOB_STATE_SUCCEEDED',
            'JOB_STATE_FAILED',
            'JOB_STATE_CANCELLED',
            'JOB_STATE_PARTIALLY_SUCCEEDED',
        ]);

        // Vertex AIのstateをBatchJobState::tryFromString()で処理できる形式に変換
        return [
            'name' => $vertex_response['name'] ?? '',
            'done' => $is_done,
            'error' => isset($vertex_response['error']) ? $vertex_response['error'] : null,
            'response' => null,
            'metadata' => [
                'model' => $vertex_response['model'] ?? null,
                'displayName' => $vertex_response['displayName'] ?? null,
                'createTime' => $vertex_response['createTime'] ?? null,
                'updateTime' => $vertex_response['updateTime'] ?? null,
                'state' => $state,
                'batchStats' => [
                    'totalCount' => $vertex_response['completionStats']['totalCount'] ?? null,
                    'succeededCount' => $vertex_response['completionStats']['successfulCount'] ?? null,
                    'failedCount' => $vertex_response['completionStats']['failedCount'] ?? null,
                ],
                'output' => null,
            ],
        ];
    }

    /**
     * Vertex AIジョブが完了状態かどうか
     */
    private function isJobCompleted(array $response): bool
    {
        return in_array($response['state'] ?? '', [
            'JOB_STATE_SUCCEEDED',
            'JOB_STATE_PARTIALLY_SUCCEEDED',
        ]);
    }

    /**
     * GCSの出力先から結果を取得してインラインレスポンス形式に変換
     */
    private function fetchResultsFromGcs(array $job_response): array
    {
        $output_info = $job_response['outputInfo'] ?? [];
        $gcs_output_directory = $output_info['gcsOutputDirectory'] ?? null;

        if ($gcs_output_directory === null) {
            return [];
        }

        // gs://bucket/path/ 形式からbucketとprefixを抽出
        $parsed = $this->parseGcsUri($gcs_output_directory);
        if ($parsed === null) {
            return [];
        }

        // GCSから出力ファイル一覧を取得
        $files = $this->listGcsObjects($parsed['bucket'], $parsed['path']);

        $inlined_responses = [];
        foreach ($files as $file) {
            // predictions*.jsonl ファイルのみ処理
            if (!str_contains($file, 'predictions') || !str_ends_with($file, '.jsonl')) {
                continue;
            }

            $content = $this->downloadFromGcs($parsed['bucket'], $file);
            if ($content === null) {
                continue;
            }

            // JSONL形式をパース（各行が1リクエストの結果）
            $lines = array_filter(explode("\n", trim($content)));
            foreach ($lines as $line) {
                $result = json_decode($line, true);
                if ($result === null) {
                    continue;
                }

                $inlined_responses[] = $this->transformPredictionToInlinedResponse($result);
            }
        }

        return $inlined_responses;
    }

    /**
     * Vertex AI予測結果をBatchInlinedResponseDto互換形式に変換
     */
    private function transformPredictionToInlinedResponse(array $prediction): array
    {
        $response = $prediction['response'] ?? $prediction;
        $candidates = $response['candidates'] ?? [];
        $metadata = $prediction['metadata'] ?? $prediction['request']['metadata'] ?? null;
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $key = $decoded['key'] ?? '';
        } else {
            $key = $metadata['key'] ?? '';
        }

        return [
            'response' => [
                'candidates' => $candidates,
                'usageMetadata' => $response['usageMetadata'] ?? [],
                'modelVersion' => $response['modelVersion'] ?? '',
            ],
            'metadata' => [
                'key' => $key,
            ],
        ];
    }

    /**
     * GCSにファイルをアップロード
     */
    private function uploadToGcs(string $object_path, string $content): void
    {
        $url = 'https://storage.googleapis.com/upload/storage/v1/b/'
            . urlencode($this->gcs_bucket)
            . '/o?uploadType=media&name=' . urlencode($object_path);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->auth->getAccessToken(),
            'Content-Type: application/octet-stream',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status < 200 || $http_status >= 300) {
            throw new GeminiApiRequestException(
                'GCSアップロードに失敗しました: ' . $http_status . ' - ' . ($response ?: ''),
                $http_status,
            );
        }
    }

    /**
     * GCSからファイルをダウンロード
     */
    private function downloadFromGcs(string $bucket, string $object_path): ?string
    {
        $url = 'https://storage.googleapis.com/storage/v1/b/'
            . urlencode($bucket)
            . '/o/' . urlencode($object_path)
            . '?alt=media';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->auth->getAccessToken(),
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status < 200 || $http_status >= 300) {
            return null;
        }

        return $response;
    }

    /**
     * GCSオブジェクト一覧を取得
     */
    private function listGcsObjects(string $bucket, string $prefix): array
    {
        $url = 'https://storage.googleapis.com/storage/v1/b/'
            . urlencode($bucket)
            . '/o?prefix=' . urlencode($prefix);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->auth->getAccessToken(),
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status < 200 || $http_status >= 300) {
            return [];
        }

        $data = json_decode($response, true);
        $objects = [];
        foreach ($data['items'] ?? [] as $item) {
            $objects[] = $item['name'];
        }

        return $objects;
    }

    /**
     * GCS URIをパース
     *
     * @return array{bucket: string, path: string}|null
     */
    private function parseGcsUri(string $uri): ?array
    {
        if (!str_starts_with($uri, 'gs://')) {
            return null;
        }

        $without_prefix = substr($uri, 5);
        $slash_pos = strpos($without_prefix, '/');

        if ($slash_pos === false) {
            return ['bucket' => $without_prefix, 'path' => ''];
        }

        return [
            'bucket' => substr($without_prefix, 0, $slash_pos),
            'path' => substr($without_prefix, $slash_pos + 1),
        ];
    }

    /**
     * バッチ名からジョブIDを抽出
     *
     * "projects/.../batchPredictionJobs/123" → "123"
     * "123" → "123"
     */
    private function extractJobId(string $batch_name): string
    {
        if (str_contains($batch_name, '/batchPredictionJobs/')) {
            $parts = explode('/batchPredictionJobs/', $batch_name);
            return end($parts);
        }

        // Gemini形式 "batches/xxx" にも対応
        if (str_starts_with($batch_name, 'batches/')) {
            return substr($batch_name, strlen('batches/'));
        }

        return $batch_name;
    }

    /**
     * batchPredictionJobs API の URL
     */
    private function getBatchJobsUrl(): string
    {
        return $this->getVertexAiHost() . '/v1/projects/'
            . $this->project_id . '/locations/' . $this->location . '/batchPredictionJobs';
    }

    /**
     * Vertex AI APIホストを取得
     *
     * globalの場合: https://aiplatform.googleapis.com
     * リージョナルの場合: https://{location}-aiplatform.googleapis.com
     */
    private function getVertexAiHost(): string
    {
        if ($this->location === 'global') {
            return 'https://aiplatform.googleapis.com';
        }

        return 'https://' . $this->location . '-aiplatform.googleapis.com';
    }

    /**
     * 共通ヘッダー
     */
    private function getHeaders(): array
    {
        return [
            'Authorization: Bearer ' . $this->auth->getAccessToken(),
            'Content-Type: application/json',
        ];
    }
}
