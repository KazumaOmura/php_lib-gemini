<?php

namespace YouCast\Gemini;

use YouCast\Gemini\Enums\AiModel;
use YouCast\Gemini\Dto\ResponseDto;
use YouCast\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Common\Curl;

/**
 * Google Gemini APIのクライアント
 */
class GeminiClient
{
    private array $request_data = [];

    public function __construct(
        private string $api_key,
        private AiModel $model
    ) {}

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
                    ...($json_mode ? [
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                        ]
                    ] : []),
                ],
                true => $this->request_data = [
                    'batch' => [
                        'display_name' => 'my-batch-requests',
                        'input_config' => [
                            'requests' => [
                                'requests' => array_map(
                                    function ($part, $idx) {
                                        return [
                                            'request' => [
                                                'contents' => [
                                                    [
                                                        'parts' => [$part]
                                                    ]
                                                ]
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
