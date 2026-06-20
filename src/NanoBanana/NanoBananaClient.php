<?php

namespace YouCast\Gemini\NanoBanana;

use YouCast\Gemini\NanoBanana\Enums\ImageModel;
use YouCast\Gemini\NanoBanana\Dto\NanoBananaResponseDto;
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\NanoBanana\Exceptions\ImageProcessingException;
use YouCast\Gemini\Exceptions\GeminiFileOperationException;
use YouCast\Gemini\NanoBanana\Builders\PhotographyPromptBuilder;
use YouCast\Gemini\NanoBanana\Builders\StickerPromptBuilder;
use Illuminate\Support\Facades\Http;

/**
 * Google Gemini APIを使用して画像を生成するクライアントクラス
 */
class NanoBananaClient
{

    private array $request_data = [];

    public function __construct(
        private string $api_key,
        private ImageModel $model,
        private bool $is_image_validation = true
    ) {}

    /**
     * Gemini APIを使用して画像を生成し、Base64デコードしてファイルに保存する
     *
     * @param string $prompt 画像生成のプロンプト
     * @param string $output_path 出力ファイルパス
     * @return NanoBananaResponseDto
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function generateImage(string $prompt, string $output_path): NanoBananaResponseDto
    {
        try {
            // APIリクエストの準備
            $request_data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ];

            // リクエストデータを保存
            $this->request_data = $request_data;

            // APIリクエストの実行
            $response = Http::withHeaders([
                'x-goog-api-key' => $this->api_key,
                'Content-Type' => 'application/json'
            ])->timeout(120) // タイムアウトを120秒に設定
                ->post($this->model->getApiUrl(), $request_data);

            if (!$response->successful()) {
                throw new GeminiApiRequestException(
                    'APIリクエストが失敗しました: ' . $response->status() . ' - ' . $response->body(),
                    0,
                    null,
                    [
                        'status_code' => $response->status(),
                        'response_body' => $response->body(),
                        'prompt' => $prompt
                    ]
                );
            }

            $dto = new NanoBananaResponseDto($response->json());

            if (empty($dto->getBase64())) {
                throw new ImageProcessingException(
                    'レスポンスからBase64データを抽出できませんでした',
                    0,
                    null,
                    [
                        'response' => $dto,
                        'prompt' => $prompt
                    ]
                );
            }

            // Base64デコードしてファイルに保存
            $image_data = base64_decode($dto->getBase64());
            if ($image_data === false) {
                throw new ImageProcessingException(
                    'Base64デコードに失敗しました',
                    0,
                    null,
                    [
                        'base64_data_length' => strlen($dto->getBase64()),
                        'prompt' => $prompt
                    ]
                );
            }

            // ディレクトリが存在しない場合は作成
            $output_dir = dirname($output_path);
            if (!is_dir($output_dir)) {
                if (!mkdir($output_dir, 0755, true)) {
                    throw new GeminiFileOperationException(
                        '出力ディレクトリの作成に失敗しました: ' . $output_dir,
                        0,
                        null,
                        [
                            'output_dir' => $output_dir,
                            'prompt' => $prompt
                        ]
                    );
                }
            }

            // ファイルに保存
            $result = file_put_contents($output_path, $image_data);
            if ($result === false) {
                throw new GeminiFileOperationException(
                    'ファイルの保存に失敗しました: ' . $output_path,
                    0,
                    null,
                    [
                        'output_path' => $output_path,
                        'image_data_size' => strlen($image_data),
                        'prompt' => $prompt
                    ]
                );
            }

            return $dto;
        } catch (GeminiApiKeyException | GeminiApiRequestException | ImageProcessingException | GeminiFileOperationException $e) {
            // カスタム例外はそのまま再スロー
            throw $e;
        } catch (\Exception $e) {
            // その他の例外はApiRequestExceptionとしてラップ
            throw new GeminiApiRequestException(
                '予期しないエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'prompt' => $prompt,
                    'output_path' => $output_path,
                    'original_error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * デフォルトのナノバナナ画像を生成する
     *
     * @param string $output_path 出力ファイルパス
     * @return NanoBananaResponseDto 成功した場合true
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function generateNanoBananaImage(string $output_path): NanoBananaResponseDto
    {
        $prompt = 'Create a picture of a nano banana dish in a fancy restaurant with a Gemini theme';
        return $this->generateImage($prompt, $output_path);
    }

    /**
     * 画像を編集する（テキストと画像による画像変換）
     *
     * @param string $prompt 画像編集のプロンプト
     * @param array $input_image_paths 編集する画像のパス
     * @param string $output_path 出力ファイルパス
     * @return NanoBananaResponseDto 成功した場合true
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function editImage(string $prompt, array $input_image_paths, string $output_path): NanoBananaResponseDto
    {
        try {
            $image_data_list = [];
            // URLかローカルファイルパスかを判断
            foreach ($input_image_paths as $image_path) {
                if (filter_var($image_path, FILTER_VALIDATE_URL)) {
                    $image_data_list[] = [
                        'url' => $image_path,
                        'data' => file_get_contents($image_path),
                    ];
                } else {
                    $image_data_list[] = [
                        'url' => null,
                        'data' => file_get_contents($image_path),
                    ];
                }
            }

            // 画像ファイルの存在チェックとmimeタイプの取得
            $inline_data_list = [];
            foreach ($image_data_list as $image_data_item) {
                if ($image_data_item['data'] === false) {
                    throw new GeminiFileOperationException(
                        '画像ファイルの読み込みに失敗しました: ' . $image_data_item['url'],
                        0,
                        null,
                        ['image_path' => $image_data_item['url']]
                    );
                }

                $mime_type = $this->getMimeType($image_data_item['url']);
                if (empty($mime_type)) {
                    if ($this->is_image_validation) {
                        throw new ImageProcessingException(
                            'サポートされていない画像形式です: ' . $image_data_item['url'],
                            0,
                            null,
                            ['image_path' => $image_data_item['url']]
                        );
                    }
                }

                $inline_data_list[] = [
                    'inline_data' => [
                        'mime_type' => $mime_type,
                        'data' => base64_encode($image_data_item['data'])
                    ]
                ];
            }

            // APIリクエストの準備
            $request_data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            $inline_data_list
                        ]
                    ]
                ]
            ];

            // リクエストデータを保存
            $this->request_data = $request_data;

            // APIリクエストの実行
            $response = Http::withHeaders([
                'x-goog-api-key' => $this->api_key,
                'Content-Type' => 'application/json'
            ])->post($this->model->getApiUrl(), $request_data);

            if (!$response->successful()) {
                throw new GeminiApiRequestException(
                    'APIリクエストが失敗しました: ' . $response->status() . ' - ' . $response->body(),
                    0,
                    null,
                    [
                        'status_code' => $response->status(),
                        'response_body' => $response->body(),
                        'prompt' => $prompt,
                        'image_path' => $image_path
                    ]
                );
            }

            // $response_data = $response->json();
            $dto = new NanoBananaResponseDto($response->json());


            // Base64デコードしてファイルに保存
            $image_data = base64_decode($dto->getBase64());
            if ($image_data === false) {
                throw new ImageProcessingException(
                    'Base64デコードに失敗しました',
                    0,
                    null,
                    [
                        'base64_data_length' => strlen($dto->getBase64()),
                        'prompt' => $prompt
                    ]
                );
            }

            // ディレクトリが存在しない場合は作成
            $output_dir = dirname($output_path);
            if (!is_dir($output_dir)) {
                if (!mkdir($output_dir, 0755, true)) {
                    throw new GeminiFileOperationException(
                        '出力ディレクトリの作成に失敗しました: ' . $output_dir,
                        0,
                        null,
                        [
                            'output_dir' => $output_dir,
                            'prompt' => $prompt
                        ]
                    );
                }
            }

            // ファイルに保存
            $result = file_put_contents($output_path, $image_data);
            if ($result === false) {
                throw new GeminiFileOperationException(
                    'ファイルの保存に失敗しました: ' . $output_path,
                    0,
                    null,
                    [
                        'output_path' => $output_path,
                        'image_data_size' => strlen($image_data),
                        'prompt' => $prompt
                    ]
                );
            }

            return $dto;
        } catch (GeminiApiKeyException | GeminiApiRequestException | ImageProcessingException | GeminiFileOperationException $e) {
            // カスタム例外はそのまま再スロー
            throw $e;
        } catch (\Exception $e) {
            // その他の例外はApiRequestExceptionとしてラップ
            throw new GeminiApiRequestException(
                '予期しないエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'prompt' => $prompt,
                    'output_path' => $output_path,
                    'original_error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * 画像ファイルのMIMEタイプを取得する
     *
     * @param string $image_path 画像ファイルのパス
     * @return string|null MIMEタイプ
     */
    private function getMimeType(string $image_path): ?string
{
    // URLの場合
    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
        $content = file_get_contents($image_path);
        if ($content === false) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $content);
        finfo_close($finfo);
    } else {
        // ローカルファイルの場合
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $image_path);
        finfo_close($finfo);
    }

    return match ($mime_type) {
        'image/jpeg', 'image/jpg' => 'image/jpeg',
        'image/png'  => 'image/png',
        'image/gif'  => 'image/gif',
        'image/webp' => 'image/webp',
        default      => null,
    };
}

    /**
     * 現在のAPIキーを取得する（マスク済み）
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return substr($this->api_key, 0, 8) . '...' . substr($this->api_key, -4);
    }

    /**
     * フォトリアリスティックな画像を生成する
     *
     * @param string $subject 被写体
     * @param string $output_path 出力ファイルパス
     * @param array $photography_params 写真用語のパラメータ
     * @return NanoBananaResponseDto 成功した場合true
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function generatePhotorealisticImage(string $subject, string $output_path, array $photography_params = []): NanoBananaResponseDto
    {
        $builder = new PhotographyPromptBuilder();
        $builder->setSubject($subject);

        // 写真用語のパラメータを適用
        if (isset($photography_params['camera_angle'])) {
            $builder->setCameraAngle($photography_params['camera_angle']);
        }
        if (isset($photography_params['lens_type'])) {
            $builder->setLensType($photography_params['lens_type']);
        }
        if (isset($photography_params['lighting'])) {
            $builder->setLighting($photography_params['lighting']);
        }
        if (isset($photography_params['mood'])) {
            $builder->setMood($photography_params['mood']);
        }
        if (isset($photography_params['background'])) {
            $builder->setBackground($photography_params['background']);
        }
        if (isset($photography_params['style'])) {
            $builder->setStyle($photography_params['style']);
        }
        if (isset($photography_params['details']) && is_array($photography_params['details'])) {
            foreach ($photography_params['details'] as $detail) {
                $builder->addDetail($detail);
            }
        }
        if (isset($photography_params['quality'])) {
            $builder->setQuality($photography_params['quality']);
        }
        if (isset($photography_params['preset'])) {
            $builder->applyPreset($photography_params['preset']);
        }

        $prompt = $builder->build();
        return $this->generateImage($prompt, $output_path);
    }

    /**
     * プリセットを使用してフォトリアリスティックな画像を生成する
     *
     * @param string $subject 被写体
     * @param string $output_path 出力ファイルパス
     * @param string $preset プリセット名 (portrait, landscape, macro, street, studio)
     * @param array $additional_params 追加のパラメータ
     * @return NanoBananaResponseDto 成功した場合true
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function generatePhotorealisticImageWithPreset(string $subject, string $output_path, string $preset, array $additional_params = []): NanoBananaResponseDto
    {
        $params = array_merge(['preset' => $preset], $additional_params);
        return $this->generatePhotorealisticImage($subject, $output_path, $params);
    }

    /**
     * フォトリアリスティックな画像を編集する
     *
     * @param string $subject 被写体の説明
     * @param string $image_path 編集する画像のパス
     * @param string $output_path 出力ファイルパス
     * @param array $photography_params 写真用語のパラメータ
     * @return bool 成功した場合true
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function editPhotorealisticImage(string $subject, string $image_path, string $output_path, array $photography_params = []): bool
    {
        $builder = new PhotographyPromptBuilder();
        $builder->setSubject($subject);

        // 写真用語のパラメータを適用
        if (isset($photography_params['camera_angle'])) {
            $builder->setCameraAngle($photography_params['camera_angle']);
        }
        if (isset($photography_params['lens_type'])) {
            $builder->setLensType($photography_params['lens_type']);
        }
        if (isset($photography_params['lighting'])) {
            $builder->setLighting($photography_params['lighting']);
        }
        if (isset($photography_params['mood'])) {
            $builder->setMood($photography_params['mood']);
        }
        if (isset($photography_params['background'])) {
            $builder->setBackground($photography_params['background']);
        }
        if (isset($photography_params['style'])) {
            $builder->setStyle($photography_params['style']);
        }
        if (isset($photography_params['details']) && is_array($photography_params['details'])) {
            foreach ($photography_params['details'] as $detail) {
                $builder->addDetail($detail);
            }
        }
        if (isset($photography_params['quality'])) {
            $builder->setQuality($photography_params['quality']);
        }
        if (isset($photography_params['preset'])) {
            $builder->applyPreset($photography_params['preset']);
        }

        $prompt = $builder->build();

        return $this->editImage($prompt, $image_path, $output_path)->getBase64();
    }

    /**
     * ステッカーを生成する
     *
     * @param string $subject ステッカーの被写体
     * @param string $output_path 出力ファイルパス
     * @param array $sticker_params ステッカーのパラメータ
     * @return NanoBananaResponseDto 成功した場合true
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function generateSticker(string $subject, string $output_path, array $sticker_params = []): NanoBananaResponseDto
    {
        $builder = new StickerPromptBuilder();
        $builder->setSubject($subject);

        // ステッカーパラメータを適用
        if (isset($sticker_params['style'])) {
            $builder->setStyle($sticker_params['style']);
        }
        if (isset($sticker_params['background'])) {
            $builder->setBackground($sticker_params['background']);
        }
        if (isset($sticker_params['outline'])) {
            $builder->setOutline($sticker_params['outline']);
        }
        if (isset($sticker_params['shading'])) {
            $builder->setShading($sticker_params['shading']);
        }
        if (isset($sticker_params['color_palette'])) {
            $builder->setColorPalette($sticker_params['color_palette']);
        }
        if (isset($sticker_params['size'])) {
            $builder->setSize($sticker_params['size']);
        }
        if (isset($sticker_params['mood'])) {
            $builder->setMood($sticker_params['mood']);
        }
        if (isset($sticker_params['details']) && is_array($sticker_params['details'])) {
            foreach ($sticker_params['details'] as $detail) {
                $builder->addDetail($detail);
            }
        }
        if (isset($sticker_params['preset'])) {
            $builder->applyPreset($sticker_params['preset']);
        }

        $prompt = $builder->build();
        return $this->generateImage($prompt, $output_path);
    }

    /**
     * イラストを生成する
     *
     * @param string $subject イラストの被写体
     * @param string $output_path 出力ファイルパス
     * @param array $illustration_params イラストのパラメータ
     * @return NanoBananaResponseDto 成功した場合true
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function generateIllustration(string $subject, string $output_path, array $illustration_params = []): NanoBananaResponseDto
    {
        // デフォルトのイラストパラメータ
        $default_params = [
            'style' => 'anime',
            'background' => 'detailed',
            'quality' => 'high',
            'mood' => 'cheerful',
            'composition' => 'balanced'
        ];

        $params = array_merge($default_params, $illustration_params);

        // イラストプロンプトを構築
        $prompt = $this->buildIllustrationPrompt($subject, $params);

        return $this->generateImage($prompt, $output_path);
    }

    /**
     * プロンプトファイルを使用して画像を編集する
     *
     * @param string $prompt_file_path プロンプトファイルのパス（./Prompt/からの相対パス）
     * @param string $image_path 編集する画像のパス
     * @param string $output_path 出力ファイルパス
     * @return bool 成功した場合true
     * @throws GeminiApiKeyException
     * @throws GeminiApiRequestException
     * @throws ImageProcessingException
     * @throws GeminiFileOperationException
     */
    public function editImageWithPromptFile(string $prompt_file_path, string $image_path, string $output_path): bool
    {
        try {
            // プロンプトファイルの完全パスを構築
            $full_prompt_path = __DIR__ . '/Prompt/' . ltrim($prompt_file_path, './');

            // プロンプトファイルの存在確認
            if (!file_exists($full_prompt_path)) {
                throw new GeminiFileOperationException(
                    'プロンプトファイルが見つかりません: ' . $full_prompt_path,
                    0,
                    null,
                    ['prompt_file_path' => $full_prompt_path]
                );
            }

            // プロンプトファイルの読み込み
            $prompt = file_get_contents($full_prompt_path);
            if ($prompt === false) {
                throw new GeminiFileOperationException(
                    'プロンプトファイルの読み込みに失敗しました: ' . $full_prompt_path,
                    0,
                    null,
                    ['prompt_file_path' => $full_prompt_path]
                );
            }

            // プロンプトが空でないことを確認
            $prompt = trim($prompt);
            if (empty($prompt)) {
                throw new GeminiFileOperationException(
                    'プロンプトファイルが空です: ' . $full_prompt_path,
                    0,
                    null,
                    ['prompt_file_path' => $full_prompt_path]
                );
            }

            // editImageメソッドを呼び出し
            return $this->editImage($prompt, $image_path, $output_path)->getBase64();
        } catch (GeminiApiKeyException | GeminiApiRequestException | ImageProcessingException | GeminiFileOperationException $e) {
            // カスタム例外はそのまま再スロー
            throw $e;
        } catch (\Exception $e) {
            // その他の例外はFileOperationExceptionとしてラップ
            throw new GeminiFileOperationException(
                '予期しないエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'prompt_file_path' => $prompt_file_path,
                    'image_path' => $image_path,
                    'output_path' => $output_path,
                    'original_error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * イラストプロンプトを構築する
     *
     * @param string $subject 被写体
     * @param array $params パラメータ
     * @return string プロンプト
     */
    private function buildIllustrationPrompt(string $subject, array $params): string
    {
        $style = $params['style'] ?? 'anime';
        $background = $params['background'] ?? 'detailed';
        $quality = $params['quality'] ?? 'high';
        $mood = $params['mood'] ?? 'cheerful';
        $composition = $params['composition'] ?? 'balanced';

        $prompt = "A {$quality} quality {$style}-style illustration of {$subject}. ";

        // スタイルの詳細を追加
        if ($style === 'anime') {
            $prompt .= "The illustration features anime/manga art style with detailed character design. ";
        } elseif ($style === 'realistic') {
            $prompt .= "The illustration features realistic art style with detailed textures and lighting. ";
        } elseif ($style === 'cartoon') {
            $prompt .= "The illustration features cartoon art style with simplified but expressive design. ";
        } elseif ($style === 'watercolor') {
            $prompt .= "The illustration features watercolor art style with soft, flowing colors. ";
        }

        // ムードの指定
        $prompt .= "The overall mood should be {$mood}. ";

        // 背景の指定
        if ($background === 'transparent') {
            $prompt .= "The background must be transparent. ";
        } elseif ($background === 'detailed') {
            $prompt .= "Include a detailed, atmospheric background that complements the subject. ";
        } else {
            $prompt .= "The background should be {$background}. ";
        }

        // 構図の指定
        $prompt .= "Use a {$composition} composition with good visual balance. ";

        // 品質の指定
        if ($quality === 'high') {
            $prompt .= "The illustration should be highly detailed with professional quality rendering. ";
        }

        $prompt .= "This should be suitable for use as a digital illustration, artwork, or visual asset.";

        return $prompt;
    }

    public function getRequestData(): array
    {
        return $this->request_data;
    }
}