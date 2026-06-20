<?php

namespace YouCast\Gemini\NanoBanana\Dto;

class NanoBananaResponseDto
{
    private ?string $base64;
    private int $prompt_token_count; // リクエスト時に送信したプロンプトのトークン数
    private int $candidates_token_count; // APIが生成したレスポンスのトークン数
    private int $total_token_count; //入力と出力の合計トークン数
    private int $thoughts_token_count; // モデルの「思考プロセス」に使用されたトークン数
    private string $model_version; // 使用されたモデルのバージョン
    private string $response_id; //このレスポンスの一意な識別子

    public function __construct(
        private array $row_response
    ) {
        // parts[0] に data がある場合
        if (isset($this->row_response['candidates'][0]['content']['parts'][0]['data'])) {
            $this->base64 = $this->row_response['candidates'][0]['content']['parts'][0]['data'];
        }
        // parts[0] に inlineData がある場合
        if (isset($this->row_response['candidates'][0]['content']['parts'][0]['inlineData']['data'])) {
            $this->base64 = $this->row_response['candidates'][0]['content']['parts'][0]['inlineData']['data'];
        }
        // parts[1] に data がある場合
        if (isset($this->row_response['candidates'][0]['content']['parts'][1]['data'])) {
            $this->base64 = $this->row_response['candidates'][0]['content']['parts'][1]['data'];
        }
        // parts[1] に inlineData がある場合
        if (isset($this->row_response['candidates'][0]['content']['parts'][1]['inlineData']['data'])) {
            $this->base64 = $this->row_response['candidates'][0]['content']['parts'][1]['inlineData']['data'];
        }

        $this->prompt_token_count = $this->row_response['usageMetadata']['promptTokenCount'];
        $this->candidates_token_count = $this->row_response['usageMetadata']['candidatesTokenCount'];
        $this->total_token_count = $this->row_response['usageMetadata']['totalTokenCount'];
        $this->thoughts_token_count = $this->row_response['usageMetadata']['thoughtsTokenCount'] ?? 0;
        $this->model_version = $this->row_response['modelVersion'];
        $this->response_id = $this->row_response['responseId'];
    }

    public function getBase64()
    {
        return $this->base64;
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
            'base64' => $this->getBase64(),
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
