<?php

namespace YouCast\Gemini\Gemini\Dto;

use YouCast\Gemini\Gemini\Enums\ThinkingLevel;

/**
 * Gemini API generationConfig 用DTO
 *
 * @see https://ai.google.dev/api/generate-content#GenerationConfig
 */
class GenerationConfigDto
{
    private ?int $max_output_tokens = null;
    private ?ThinkingLevel $thinking_level = null;
    private ?string $response_mime_type = null;

    /**
     * 最大出力トークン数を設定
     */
    public function setMaxOutputTokens(int $max_output_tokens): self
    {
        $this->max_output_tokens = $max_output_tokens;
        return $this;
    }

    /**
     * Thinkingレベルを設定
     */
    public function setThinkingLevel(ThinkingLevel $thinking_level): self
    {
        $this->thinking_level = $thinking_level;
        return $this;
    }

    /**
     * レスポンスMIMEタイプを設定
     */
    public function setResponseMimeType(string $response_mime_type): self
    {
        $this->response_mime_type = $response_mime_type;
        return $this;
    }

    /**
     * Gemini API用の配列に変換
     */
    public function toArray(): array
    {
        $config = [];

        if ($this->max_output_tokens !== null) {
            $config['maxOutputTokens'] = $this->max_output_tokens;
        }

        if ($this->thinking_level !== null) {
            $config['thinkingConfig'] = [
                'thinkingLevel' => $this->thinking_level->value,
            ];
        }

        if ($this->response_mime_type !== null) {
            $config['responseMimeType'] = $this->response_mime_type;
        }

        return $config;
    }

    /**
     * 別のGenerationConfigDtoとマージした新しいインスタンスを返す
     * 引数側の値が優先される
     */
    public function merge(self $other): self
    {
        $merged = clone $this;

        if ($other->max_output_tokens !== null) {
            $merged->max_output_tokens = $other->max_output_tokens;
        }
        if ($other->thinking_level !== null) {
            $merged->thinking_level = $other->thinking_level;
        }
        if ($other->response_mime_type !== null) {
            $merged->response_mime_type = $other->response_mime_type;
        }

        return $merged;
    }
}
