<?php

namespace YouCast\Gemini\Gemini\Dto;

/**
 * Gemini API レスポンス DTO
 * @see storage/logs/gemini.json のようなレスポンスを扱う
 */
class ResponseDto
{
    private ?string $content = null;
    private ?string $thought_signature = null;
    private int $prompt_token_count = 0;
    private int $candidates_token_count = 0;
    private int $total_token_count = 0;
    private int $thoughts_token_count = 0;
    private string $model_version = '';
    private string $response_id = '';

    public function __construct(
        private array $row_response
    ) {
        // content
        if (isset($this->row_response['candidates'][0]['content']['parts'][0]['text'])) {
            $this->content = $this->row_response['candidates'][0]['content']['parts'][0]['text'];
        }

        // thoughtSignature
        if (isset($this->row_response['candidates'][0]['content']['parts'][0]['thoughtSignature'])) {
            $this->thought_signature = $this->row_response['candidates'][0]['content']['parts'][0]['thoughtSignature'];
        }

        // トークン情報等
        if (isset($this->row_response['usageMetadata']['promptTokenCount'])) {
            $this->prompt_token_count = (int) $this->row_response['usageMetadata']['promptTokenCount'];
        }
        if (isset($this->row_response['usageMetadata']['candidatesTokenCount'])) {
            $this->candidates_token_count = (int) $this->row_response['usageMetadata']['candidatesTokenCount'];
        }
        if (isset($this->row_response['usageMetadata']['totalTokenCount'])) {
            $this->total_token_count = (int) $this->row_response['usageMetadata']['totalTokenCount'];
        }
        if (isset($this->row_response['usageMetadata']['thoughtsTokenCount'])) {
            $this->thoughts_token_count = (int) $this->row_response['usageMetadata']['thoughtsTokenCount'];
        }

        $this->model_version = $this->row_response['modelVersion'] ?? '';
        $this->response_id   = $this->row_response['responseId'] ?? '';
    }

    /**
     * AIからの主な生成テキスト
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * thoughtSignature(エンタープライズ用途などに利用): オプション
     */
    public function getThoughtSignature(): ?string
    {
        return $this->thought_signature;
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

    public function getThoughtsTokenCount(): int
    {
        return $this->thoughts_token_count;
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
            'content' => $this->getContent(),
            'thought_signature' => $this->getThoughtSignature(),
            'prompt_token_count' => $this->getPromptTokenCount(),
            'candidates_token_count' => $this->getCandidatesTokenCount(),
            'total_token_count' => $this->getTotalTokenCount(),
            'thoughts_token_count' => $this->getThoughtsTokenCount(),
            'model_version' => $this->getModelVersion(),
            'response_id' => $this->getResponseId(),
            'row_response' => $this->getRowResponse(),
        ];
    }
}
