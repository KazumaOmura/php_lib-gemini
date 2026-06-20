<?php

namespace YouCast\Gemini\Gemini;

use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\HarmCategory;
use YouCast\Gemini\Gemini\Enums\HarmBlockThreshold;
use YouCast\Gemini\Gemini\Dto\GenerationConfigDto;
use YouCast\Gemini\Gemini\Dto\ResponseDto;
use YouCast\Gemini\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Common\Curl;

/**
 * Google Gemini APIのクライアント
 */
class GeminiClient
{
    private array $request_data = [];
    private array $safety_settings = [];
    private ?GenerationConfigDto $generation_config = null;

    public function __construct(
        private string $api_key,
        private AiModel $model
    ) {}

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
     * Gemini APIを使用してリクエストを送信する
     *
     * @param string $prompt プロンプト
     * @param array $inline_data inline_data形式のデータ
     * @param bool $is_batch バッチリクエストかどうか
     * @param bool $json_mode JSONモードを有効にする（純粋なJSONのみを返す）
     * @return ResponseDto | BatchResponseDto
     */
    public function request(string $prompt, array $inline_data = [], bool $is_batch = false, bool $json_mode = true): ResponseDto | BatchResponseDto
    {
        try {
            $parts = [];
            foreach ($inline_data as $data) {
                if (!$data instanceof InlineDataDto) {
                    throw new \LogicException('inline_dataの要素はInlineDataDtoインスタンスである必要があります');
                }
                $parts[] = $data->toGeminiPartArray();
            }

            $safety_settings_array = $this->buildSafetySettingsArray();

            $generation_config = $this->generation_config ?? new GenerationConfigDto();
            if ($json_mode) {
                $generation_config = $generation_config->merge(
                    (new GenerationConfigDto())->setResponseMimeType('application/json')
                );
            }
            $generation_config_array = $generation_config->toArray();

            match ($is_batch) {
                false => $this->request_data = [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                ...$parts
                            ]
                        ]
                    ],
                    ...(!empty($generation_config_array) ? [
                        'generationConfig' => $generation_config_array,
                    ] : []),
                    ...(!empty($safety_settings_array) ? [
                        'safetySettings' => $safety_settings_array,
                    ] : []),
                ],
                true => $this->request_data = [
                    'batch' => [
                        'display_name' => 'my-batch-requests',
                        'input_config' => [
                            'requests' => [
                                'requests' => array_map(
                                    function ($part, $idx) use ($safety_settings_array) {
                                        return [
                                            'request' => [
                                                'contents' => [
                                                    [
                                                        'parts' => [$part]
                                                    ]
                                                ],
                                                ...(!empty($safety_settings_array) ? [
                                                    'safetySettings' => $safety_settings_array,
                                                ] : []),
                                            ],
                                            'metadata' => [
                                                'key' => 'request-' . ($idx + 1)
                                            ]
                                        ];
                                    },
                                    $parts,
                                    array_keys($parts)
                                )
                            ]
                        ]
                    ]
                ],
            };

            $url = $is_batch ? $this->model->getBatchGenerateContentUrl() : $this->model->getGenerateContentUrl();
            $response = Curl::post($url, [
                'x-goog-api-key: ' . $this->api_key,
                'Content-Type: application/json',
            ], $this->request_data);

            return match($is_batch) {
                false => new ResponseDto($response),
                true => new BatchResponseDto($response),
            };
        } catch (GeminiApiKeyException | GeminiApiRequestException $e) {
            // カスタム例外はそのまま再スロー
            throw $e;
        } catch (\Exception $e) {
            // その他の例外はGeminiApiRequestExceptionとしてラップ
            throw new GeminiApiRequestException(
                '予期しないエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'prompt' => $prompt,
                    'original_error' => $e->getMessage()
                ]
            );
        }
    }

    public function getRequestData(): array
    {
        return $this->request_data;
    }
}
