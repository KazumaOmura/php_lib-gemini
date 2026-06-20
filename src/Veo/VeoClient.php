<?php

namespace YouCast\Gemini\Veo;

use YouCast\Gemini\Common\Curl;
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Exceptions\GeminiFileOperationException;
use YouCast\Gemini\Veo\Dto\VeoResponseDto;
use YouCast\Gemini\Veo\Enums\VideoModel;
use YouCast\Gemini\Veo\Exceptions\VideoProcessingException;

/**
 * Google Veo APIを使用して動画を生成するクライアントクラス
 *
 * predictLongRunning エンドポイントを使い、初回POST → LROポーリング → Base64動画データの取得・保存。
 */
class VeoClient
{
    private const POLL_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/';

    private array $request_data = [];

    /** ポーリング間隔（秒） */
    private int $polling_interval = 10;

    /** ポーリング最大回数 */
    private int $max_polling_attempts = 60;

    public function __construct(
        private string $api_key,
        private VideoModel $model,
    ) {
    }

    public function setPollingConfig(int $interval_seconds, int $max_attempts): self
    {
        $this->polling_interval = $interval_seconds;
        $this->max_polling_attempts = $max_attempts;
        return $this;
    }

    /**
     * Veo APIで動画を生成し、ファイルに保存する
     */
    public function generateVideo(string $prompt, string $output_path): VeoResponseDto
    {
        try {
            $this->request_data = [
                'instances' => [
                    ['prompt' => $prompt],
                ],
                'parameters' => [
                    'sampleCount' => 1,
                ],
            ];

            $headers = [
                'x-goog-api-key: ' . $this->api_key,
                'Content-Type: application/json',
            ];

            $operation = Curl::post($this->model->getApiUrl(), $headers, $this->request_data);

            $result = $this->pollOperation($operation, $prompt);

            $dto = new VeoResponseDto($result);

            if (empty($dto->getBase64())) {
                throw new VideoProcessingException(
                    'レスポンスからBase64データを抽出できませんでした',
                    0,
                    null,
                    ['response' => $result, 'prompt' => $prompt]
                );
            }

            $video_data = base64_decode($dto->getBase64(), true);
            if ($video_data === false) {
                throw new VideoProcessingException(
                    'Base64デコードに失敗しました',
                    0,
                    null,
                    ['base64_data_length' => strlen($dto->getBase64()), 'prompt' => $prompt]
                );
            }

            $output_dir = dirname($output_path);
            if (!is_dir($output_dir) && !mkdir($output_dir, 0755, true) && !is_dir($output_dir)) {
                throw new GeminiFileOperationException(
                    '出力ディレクトリの作成に失敗しました: ' . $output_dir,
                    0,
                    null,
                    ['output_dir' => $output_dir, 'prompt' => $prompt]
                );
            }

            if (file_put_contents($output_path, $video_data) === false) {
                throw new GeminiFileOperationException(
                    'ファイルの保存に失敗しました: ' . $output_path,
                    0,
                    null,
                    ['output_path' => $output_path, 'prompt' => $prompt]
                );
            }

            return $dto;
        } catch (GeminiApiKeyException | GeminiApiRequestException | VideoProcessingException | GeminiFileOperationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeminiApiRequestException(
                '予期しないエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                ['prompt' => $prompt, 'output_path' => $output_path, 'original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Long Running Operation を完了まで待機
     *
     * @internal テストから上書き可能にするため protected
     */
    protected function pollOperation(array $operation, string $prompt): array
    {
        if (!empty($operation['done'])) {
            return $operation;
        }

        $operation_name = $operation['name'] ?? null;
        if ($operation_name === null) {
            throw new GeminiApiRequestException(
                'オペレーション名が取得できませんでした',
                0,
                null,
                ['operation' => $operation, 'prompt' => $prompt]
            );
        }

        $poll_url = self::POLL_BASE_URL . $operation_name;
        $headers = ['x-goog-api-key: ' . $this->api_key];

        for ($i = 0; $i < $this->max_polling_attempts; $i++) {
            if ($this->polling_interval > 0) {
                sleep($this->polling_interval);
            }

            $result = Curl::get($poll_url, $headers);

            if (!empty($result['done'])) {
                if (isset($result['error'])) {
                    throw new GeminiApiRequestException(
                        '動画生成がエラーで完了しました: ' . ($result['error']['message'] ?? 'Unknown error'),
                        0,
                        null,
                        ['error' => $result['error'], 'operation_name' => $operation_name, 'prompt' => $prompt]
                    );
                }
                return $result;
            }
        }

        throw new GeminiApiRequestException(
            '動画生成がタイムアウトしました（' . ($this->polling_interval * $this->max_polling_attempts) . '秒）',
            0,
            null,
            ['operation_name' => $operation_name, 'polling_attempts' => $this->max_polling_attempts, 'prompt' => $prompt]
        );
    }

    public function getApiKey(): string
    {
        return substr($this->api_key, 0, 8) . '...' . substr($this->api_key, -4);
    }

    public function getRequestData(): array
    {
        return $this->request_data;
    }
}
