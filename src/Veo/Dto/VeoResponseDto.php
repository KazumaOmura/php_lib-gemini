<?php

namespace YouCast\Gemini\Veo\Dto;

class VeoResponseDto
{
    private ?string $base64 = null;
    private ?string $mime_type = null;
    private string $operation_name;

    public function __construct(
        private array $row_response
    ) {
        $this->operation_name = $this->row_response['name'] ?? '';

        // LRO完了レスポンス: response.predictions からBase64データを抽出
        $predictions = $this->row_response['response']['predictions'] ?? [];
        if (!empty($predictions)) {
            foreach ($predictions as $prediction) {
                if (isset($prediction['bytesBase64Encoded'])) {
                    $this->base64 = $prediction['bytesBase64Encoded'];
                    $this->mime_type = $prediction['mimeType'] ?? 'video/mp4';
                    break;
                }
            }
        }
    }

    public function getBase64(): ?string
    {
        return $this->base64;
    }

    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function getOperationName(): string
    {
        return $this->operation_name;
    }

    public function getRowResponse(): array
    {
        return $this->row_response;
    }

    public function toArray(): array
    {
        return [
            'base64' => $this->getBase64(),
            'mime_type' => $this->getMimeType(),
            'operation_name' => $this->getOperationName(),
            'row_response' => $this->getRowResponse(),
        ];
    }
}
