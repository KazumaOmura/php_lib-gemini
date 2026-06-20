<?php

namespace YouCast\Gemini\Gemini\Dto;

/**
 * Gemini Batch API インライン結果 DTO
 *
 * バッチ処理の各リクエストに対するレスポンスを表す
 */
class BatchInlinedResponseDto
{
    private ?string $key;
    private ?string $text;
    private array $candidates;
    private array $raw_response;

    public function __construct(array $raw_response)
    {
        $this->raw_response = $raw_response;
        $metadata = $raw_response['metadata'] ?? null;
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $this->key = $decoded['key'] ?? null;
        } else {
            $this->key = $metadata['key'] ?? null;
        }
        $this->candidates = $raw_response['response']['candidates'] ?? [];
        $this->text = $this->extractText();
    }

    /**
     * テキストコンテンツを抽出
     */
    private function extractText(): ?string
    {
        if (empty($this->candidates)) {
            return null;
        }

        return $this->candidates[0]['content']['parts'][0]['text'] ?? null;
    }

    /**
     * リクエストキー
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * レスポンステキスト
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * 候補配列
     */
    public function getCandidates(): array
    {
        return $this->candidates;
    }

    /**
     * 生レスポンス
     */
    public function getRawResponse(): array
    {
        return $this->raw_response;
    }

    /**
     * 生レスポンス（ResponseDtoとの互換性用）
     */
    public function getRowResponse(): array
    {
        return $this->raw_response;
    }

    /**
     * 合計トークン数（ResponseDtoとの互換性用）
     */
    public function getTotalTokenCount(): int
    {
        return $this->raw_response['response']['usageMetadata']['totalTokenCount'] ?? 0;
    }

    /**
     * JSONコンテンツをパースして取得
     *
     * @return array|null
     */
    public function getParsedJson(): ?array
    {
        $text = $this->getText();
        if ($text === null) {
            return null;
        }

        // ```json ... ``` 形式を処理
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return null;
    }
}
