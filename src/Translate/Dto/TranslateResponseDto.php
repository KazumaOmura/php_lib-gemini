<?php

namespace YouCast\Gemini\Translate\Dto;

/**
 * Google Cloud Translation API レスポンス DTO
 *
 * @see https://cloud.google.com/translate/docs/reference/rest/v2/translate
 */
class TranslateResponseDto
{
    /**
     * @var array<int, array{translated_text: string, detected_source_language: ?string, model: ?string}>
     */
    private array $translations = [];

    public function __construct(
        private array $row_response
    ) {
        $raw_translations = $this->row_response['data']['translations'] ?? [];
        foreach ($raw_translations as $translation) {
            $this->translations[] = [
                'translated_text' => $translation['translatedText'] ?? '',
                'detected_source_language' => $translation['detectedSourceLanguage'] ?? null,
                'model' => $translation['model'] ?? null,
            ];
        }
    }

    /**
     * 最初の翻訳結果のテキストを取得
     */
    public function getTranslatedText(): ?string
    {
        return $this->translations[0]['translated_text'] ?? null;
    }

    /**
     * 全ての翻訳結果のテキストを配列で取得
     *
     * @return array<int, string>
     */
    public function getTranslatedTexts(): array
    {
        return array_map(fn(array $t) => $t['translated_text'], $this->translations);
    }

    /**
     * 最初の翻訳結果の検出元言語を取得（sourceを指定しなかった場合のみ返る）
     */
    public function getDetectedSourceLanguage(): ?string
    {
        return $this->translations[0]['detected_source_language'] ?? null;
    }

    /**
     * 全ての翻訳結果の情報を取得
     *
     * @return array<int, array{translated_text: string, detected_source_language: ?string, model: ?string}>
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getRowResponse(): array
    {
        return $this->row_response;
    }

    public function toArray(): array
    {
        return [
            'translations' => $this->translations,
            'row_response' => $this->row_response,
        ];
    }
}
