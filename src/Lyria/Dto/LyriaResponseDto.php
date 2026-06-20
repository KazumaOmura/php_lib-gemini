<?php

namespace YouCast\Gemini\Lyria\Dto;

/**
 * Lyria（音楽生成）API レスポンス DTO
 *
 * Gemini と同じ generateContent 形式のレスポンスを返すが、
 * parts には text と inline_data（音声バイト, Base64）が含まれる。
 */
class LyriaResponseDto
{
    private ?string $text = null;
    private ?string $audio_base64 = null;
    private ?string $audio_mime_type = null;
    private int $prompt_token_count = 0;
    private int $candidates_token_count = 0;
    private int $total_token_count = 0;
    private string $model_version = '';
    private string $response_id = '';

    public function __construct(
        private array $row_response
    ) {
        $parts = $this->row_response['candidates'][0]['content']['parts'] ?? [];
        $text_parts = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text_parts[] = $part['text'];
            }

            // REST レスポンスは camelCase (inlineData) で返るが snake_case (inline_data) も許容
            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
            if ($inline !== null) {
                $this->audio_base64 = $inline['data'] ?? null;
                $this->audio_mime_type = $inline['mimeType'] ?? $inline['mime_type'] ?? null;
            }
        }

        if (!empty($text_parts)) {
            $this->text = implode('', $text_parts);
        }

        if (isset($this->row_response['usageMetadata']['promptTokenCount'])) {
            $this->prompt_token_count = (int) $this->row_response['usageMetadata']['promptTokenCount'];
        }
        if (isset($this->row_response['usageMetadata']['candidatesTokenCount'])) {
            $this->candidates_token_count = (int) $this->row_response['usageMetadata']['candidatesTokenCount'];
        }
        if (isset($this->row_response['usageMetadata']['totalTokenCount'])) {
            $this->total_token_count = (int) $this->row_response['usageMetadata']['totalTokenCount'];
        }

        $this->model_version = $this->row_response['modelVersion'] ?? '';
        $this->response_id = $this->row_response['responseId'] ?? '';
    }

    /**
     * 歌詞や楽曲構造説明などのテキスト
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * 音声データ（Base64エンコード済み）
     */
    public function getAudioBase64(): ?string
    {
        return $this->audio_base64;
    }

    /**
     * 音声データ（バイナリ）
     */
    public function getAudioBytes(): ?string
    {
        if ($this->audio_base64 === null) {
            return null;
        }
        $decoded = base64_decode($this->audio_base64, true);
        return $decoded === false ? null : $decoded;
    }

    /**
     * 音声のMIMEタイプ（audio/wav, audio/mpeg 等）
     */
    public function getAudioMimeType(): ?string
    {
        return $this->audio_mime_type;
    }

    public function hasAudio(): bool
    {
        return $this->audio_base64 !== null;
    }

    /**
     * 音声データをファイル保存
     */
    public function saveAudioTo(string $path): bool
    {
        $bytes = $this->getAudioBytes();
        if ($bytes === null) {
            return false;
        }
        return file_put_contents($path, $bytes) !== false;
    }

    public function getPromptTokenCount(): int
    {
        return $this->prompt_token_count;
    }

    public function getCandidatesTokenCount(): int
    {
        return $this->candidates_token_count;
    }

    public function getTotalTokenCount(): int
    {
        return $this->total_token_count;
    }

    public function getModelVersion(): string
    {
        return $this->model_version;
    }

    public function getResponseId(): string
    {
        return $this->response_id;
    }

    public function getRowResponse(): array
    {
        return $this->row_response;
    }

    public function toArray(): array
    {
        return [
            'text' => $this->getText(),
            'audio_base64' => $this->getAudioBase64(),
            'audio_mime_type' => $this->getAudioMimeType(),
            'has_audio' => $this->hasAudio(),
            'prompt_token_count' => $this->getPromptTokenCount(),
            'candidates_token_count' => $this->getCandidatesTokenCount(),
            'total_token_count' => $this->getTotalTokenCount(),
            'model_version' => $this->getModelVersion(),
            'response_id' => $this->getResponseId(),
            'row_response' => $this->getRowResponse(),
        ];
    }
}
