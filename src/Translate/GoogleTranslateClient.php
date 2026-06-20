<?php

namespace YouCast\Gemini\Translate;

use YouCast\Gemini\Common\Curl;
use YouCast\Gemini\Translate\Dto\TranslateResponseDto;
use YouCast\Gemini\Translate\Enums\TranslateFormat;
use YouCast\Gemini\Translate\Enums\TranslateLanguage;
use YouCast\Gemini\Translate\Enums\TranslateModel;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Translate\Exceptions\TranslateApiKeyException;
use YouCast\Gemini\Translate\Exceptions\TranslateApiRequestException;

/**
 * Google Cloud Translation API のクライアント
 *
 * @see https://cloud.google.com/translate/docs/reference/rest/v2/translate
 */
class GoogleTranslateClient
{
    private const BASE_URL = 'https://translation.googleapis.com/language/translate/v2';

    private array $request_data = [];

    public function __construct(
        private string $api_key
    ) {}

    /**
     * テキストを翻訳する
     *
     * @param string|array           $text   翻訳対象のテキスト（配列指定で複数一括翻訳）
     * @param TranslateLanguage      $target 翻訳先の言語
     * @param TranslateLanguage|null $source 翻訳元の言語（nullの場合はAPI側で自動検出）
     * @param TranslateFormat        $format テキスト形式
     * @param TranslateModel|null    $model  使用する翻訳モデル（nullでAPIデフォルト）
     * @return TranslateResponseDto
     */
    public function translate(
        string|array $text,
        TranslateLanguage $target,
        ?TranslateLanguage $source = null,
        TranslateFormat $format = TranslateFormat::TEXT,
        ?TranslateModel $model = null,
    ): TranslateResponseDto {
        try {
            $this->request_data = [
                'q' => $text,
                'target' => $target->value,
                'format' => $format->value,
                ...($source !== null ? ['source' => $source->value] : []),
                ...($model !== null ? ['model' => $model->value] : []),
            ];

            $url = self::BASE_URL . '?' . http_build_query(['key' => $this->api_key]);
            $response = Curl::post($url, [
                'Content-Type: application/json',
            ], $this->request_data);

            return new TranslateResponseDto($response);
        } catch (TranslateApiKeyException | TranslateApiRequestException $e) {
            // Translate用のカスタム例外はそのまま再スロー
            throw $e;
        } catch (GeminiApiRequestException $e) {
            // Curlヘルパーが投げるGemini系例外はTranslate系にラップし直す
            throw new TranslateApiRequestException(
                $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'text' => $text,
                    'target' => $target->value,
                ]
            );
        } catch (\Exception $e) {
            // その他の例外はTranslateApiRequestExceptionとしてラップ
            throw new TranslateApiRequestException(
                '予期しないエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                [
                    'text' => $text,
                    'target' => $target->value,
                    'original_error' => $e->getMessage(),
                ]
            );
        }
    }

    public function getRequestData(): array
    {
        return $this->request_data;
    }
}
