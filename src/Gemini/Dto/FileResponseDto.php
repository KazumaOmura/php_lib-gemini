<?php

namespace YouCast\Gemini\Gemini\Dto;

/**
 * Gemini File API アップロード結果 DTO
 */
class FileResponseDto
{
    private string $name;
    private string $display_name;
    private string $mime_type;
    private int $size_bytes;
    private string $create_time;
    private string $update_time;
    private string $expiration_time;
    private string $sha256_hash;
    private string $uri;
    private string $state;

    public function __construct(array $response)
    {
        $file = $response['file'] ?? $response;

        $this->name = $file['name'] ?? '';
        $this->display_name = $file['displayName'] ?? '';
        $this->mime_type = $file['mimeType'] ?? '';
        $this->size_bytes = (int) ($file['sizeBytes'] ?? 0);
        $this->create_time = $file['createTime'] ?? '';
        $this->update_time = $file['updateTime'] ?? '';
        $this->expiration_time = $file['expirationTime'] ?? '';
        $this->sha256_hash = $file['sha256Hash'] ?? '';
        $this->uri = $file['uri'] ?? '';
        $this->state = $file['state'] ?? '';
    }

    /**
     * ファイル名（files/xxx形式）
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 表示名
     */
    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    /**
     * MIMEタイプ
     */
    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    /**
     * ファイルサイズ（バイト）
     */
    public function getSizeBytes(): int
    {
        return $this->size_bytes;
    }

    /**
     * 作成日時
     */
    public function getCreateTime(): string
    {
        return $this->create_time;
    }

    /**
     * 更新日時
     */
    public function getUpdateTime(): string
    {
        return $this->update_time;
    }

    /**
     * 有効期限
     */
    public function getExpirationTime(): string
    {
        return $this->expiration_time;
    }

    /**
     * SHA256ハッシュ
     */
    public function getSha256Hash(): string
    {
        return $this->sha256_hash;
    }

    /**
     * ファイルURI（generateContentで使用）
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * ファイル状態（PROCESSING, ACTIVE, FAILED）
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * ファイルがアクティブかどうか
     */
    public function isActive(): bool
    {
        return $this->state === 'ACTIVE';
    }

    /**
     * ファイルが処理中かどうか
     */
    public function isProcessing(): bool
    {
        return $this->state === 'PROCESSING';
    }

    /**
     * generateContent用のfile_dataパーツを生成
     */
    public function toFileDataPart(): array
    {
        return [
            'file_data' => [
                'mime_type' => $this->mime_type,
                'file_uri' => $this->uri,
            ]
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->display_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
            'expiration_time' => $this->expiration_time,
            'sha256_hash' => $this->sha256_hash,
            'uri' => $this->uri,
            'state' => $this->state,
        ];
    }
}
