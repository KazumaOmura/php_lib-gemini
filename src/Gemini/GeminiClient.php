<?php

namespace YouCast\Gemini\Gemini;

use YouCast\Gemini\Common\Curl;
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Gemini\Dto\AudioResponseDto;
use YouCast\Gemini\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Gemini\Dto\GenerationConfigDto;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Gemini\Dto\ResponseDto;
use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\HarmBlockThreshold;
use YouCast\Gemini\Gemini\Enums\HarmCategory;
use YouCast\Gemini\Gemini\Enums\SpeechSpeed;
use YouCast\Gemini\Gemini\Enums\Voice;

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

    /**
     * テキスト生成（フリーテキスト返却）
     *
     * Gemini API でプレーンなテキストレスポンスを得る最短経路。
     *
     * @param string $prompt プロンプト
     * @param InlineDataDto[] $inline_data 画像・PDF・file_uri等の追加入力
     */
    public function generateText(string $prompt, array $inline_data = []): ResponseDto
    {
        return $this->request($prompt, $inline_data, is_batch: false, json_mode: false);
    }

    /**
     * JSON生成（responseMimeType=application/json）
     *
     * モデルが純粋なJSONだけを返すよう強制する。`json_decode($response->getContent(), true)` でそのまま使える。
     *
     * @param string $prompt プロンプト
     * @param InlineDataDto[] $inline_data 画像・PDF・file_uri等の追加入力
     */
    public function generateJson(string $prompt, array $inline_data = []): ResponseDto
    {
        return $this->request($prompt, $inline_data, is_batch: false, json_mode: true);
    }

    /**
     * 音声生成 (Text-to-Speech)
     *
     * Gemini TTSを使ってテキストを読み上げ、PCM音声(Base64)を返す。
     *
     * - コンストラクタの $model がTTSモデル (`AiModel::isTts() === true`) なら、それを使う。
     * - そうでなければ $model 引数のフォールバック、それも null なら GEMINI_2_5_FLASH_TTS を使う。
     *
     * @param string $prompt 読み上げるテキスト（"Say cheerfully: ..." のようにスタイル指示も可）
     * @param Voice $voice 使う音声（既定: KORE）
     * @param AiModel|null $model 明示的にTTSモデルを指定したい場合
     * @param SpeechSpeed|null $speed 話速プリセット。指定するとプロンプト先頭に自然言語の指示を自動付与する
     */
    public function generateAudio(
        string $prompt,
        Voice $voice = Voice::KORE,
        ?AiModel $model = null,
        ?SpeechSpeed $speed = null,
    ): AudioResponseDto {
        $tts_model = $this->resolveTtsModel($model);

        return $this->executeAudioRequest(
            prompt: $this->applySpeedInstruction($prompt, $speed),
            model: $tts_model,
            speech_config: [
                'voiceConfig' => [
                    'prebuiltVoiceConfig' => ['voiceName' => $voice->value],
                ],
            ],
        );
    }

    /**
     * 複数話者の音声生成 (Multi-speaker TTS)
     *
     * 会話形式のテキストと、各話者名 → Voice のマッピングを与えると、話者ごとに音声を切り替えて読み上げる。
     *
     * @param string $prompt 会話形式のプロンプト（例: "TTS the following conversation between Joe and Jane: Joe: ... Jane: ..."）
     * @param array<string, Voice> $speakers ['Joe' => Voice::KORE, 'Jane' => Voice::PUCK]
     * @param AiModel|null $model 明示的にTTSモデルを指定したい場合
     * @param SpeechSpeed|null $speed 話速プリセット。指定するとプロンプト先頭に自然言語の指示を自動付与する
     */
    public function generateMultiSpeakerAudio(
        string $prompt,
        array $speakers,
        ?AiModel $model = null,
        ?SpeechSpeed $speed = null,
    ): AudioResponseDto {
        if (empty($speakers)) {
            throw new \InvalidArgumentException('speakers は1人以上指定してください');
        }

        $speaker_configs = [];
        foreach ($speakers as $speaker_name => $voice) {
            if (!$voice instanceof Voice) {
                throw new \InvalidArgumentException('speakers の値は Voice enum である必要があります');
            }
            $speaker_configs[] = [
                'speaker' => $speaker_name,
                'voiceConfig' => [
                    'prebuiltVoiceConfig' => ['voiceName' => $voice->value],
                ],
            ];
        }

        return $this->executeAudioRequest(
            prompt: $this->applySpeedInstruction($prompt, $speed),
            model: $this->resolveTtsModel($model),
            speech_config: [
                'multiSpeakerVoiceConfig' => [
                    'speakerVoiceConfigs' => $speaker_configs,
                ],
            ],
        );
    }

    /**
     * 話速プリセットをプロンプト先頭に自然言語の指示として付与する
     */
    private function applySpeedInstruction(string $prompt, ?SpeechSpeed $speed): string
    {
        if ($speed === null) {
            return $prompt;
        }
        $instruction = $speed->toPromptInstruction();
        if ($instruction === null) {
            return $prompt;
        }
        return $instruction . ': ' . $prompt;
    }

    /**
     * TTS用モデルを決定する
     */
    private function resolveTtsModel(?AiModel $model): AiModel
    {
        if ($model !== null) {
            return $model;
        }
        if ($this->model->isTts()) {
            return $this->model;
        }
        return AiModel::GEMINI_2_5_FLASH_TTS;
    }

    /**
     * TTSリクエスト本体
     *
     * @param array $speech_config "voiceConfig" or "multiSpeakerVoiceConfig" を含むspeechConfig
     */
    private function executeAudioRequest(string $prompt, AiModel $model, array $speech_config): AudioResponseDto
    {
        try {
            $this->request_data = [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseModalities' => ['AUDIO'],
                    'speechConfig' => $speech_config,
                ],
            ];

            $response = Curl::post(
                $model->getGenerateContentUrl(),
                [
                    'x-goog-api-key: ' . $this->api_key,
                    'Content-Type: application/json',
                ],
                $this->request_data,
            );

            return new AudioResponseDto($response);
        } catch (GeminiApiKeyException | GeminiApiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeminiApiRequestException(
                '音声生成で予期しないエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'prompt' => $prompt,
                    'model' => $model->value,
                    'original_error' => $e->getMessage(),
                ]
            );
        }
    }
}
