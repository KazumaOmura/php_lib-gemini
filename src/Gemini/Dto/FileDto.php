<?php

namespace YouCast\Gemini\Gemini\Dto;

use YouCast\Gemini\Exceptions\GeminiFileOperationException;

/**
 * Gemini APIのFile API用DTO
 *
 * ファイルの種類に応じてFactory Methodを使用してインスタンスを生成する
 * - ローカルファイル: FileDto::fromLocalFile($path, $displayName)
 * - バイナリデータ: FileDto::fromBinary($data, $mimeType, $displayName)
 * - URL: FileDto::fromUrl($url, $displayName) ※将来拡張用
 */
class FileDto
{
    public const SOURCE_LOCAL_FILE = 'local_file';
    public const SOURCE_BINARY = 'binary';
    public const SOURCE_URL = 'url';

    private function __construct(
        private string $source_type,
        private string $data,
        private string $display_name,
        private string $mime_type,
        private int $file_size,
    ) {
    }

    /**
     * ローカルファイルからDTOを生成
     *
     * @param string $file_path ファイルパス
     * @param string $display_name 表示名
     * @return self
     * @throws GeminiFileOperationException
     */
    public static function fromLocalFile(string $file_path, string $display_name): self
    {
        if (!file_exists($file_path)) {
            throw new GeminiFileOperationException(
                "ファイルが見つかりません: {$file_path}",
                0,
                null,
                ['file' => $file_path]
            );
        }

        $mime_type = self::detectMimeType($file_path);
        $file_size = filesize($file_path);

        if ($file_size === false) {
            throw new GeminiFileOperationException(
                "ファイルサイズの取得に失敗しました: {$file_path}",
                0,
                null,
                ['file' => $file_path]
            );
        }

        return new self(
            source_type: self::SOURCE_LOCAL_FILE,
            data: $file_path,
            display_name: $display_name,
            mime_type: $mime_type,
            file_size: $file_size,
        );
    }

    /**
     * バイナリデータからDTOを生成
     *
     * @param string $binary_data バイナリデータ
     * @param string $mime_type MIMEタイプ（バイナリからは自動検出できないため必須）
     * @param string $display_name 表示名
     * @return self
     */
    public static function fromBinary(string $binary_data, string $mime_type, string $display_name): self
    {
        return new self(
            source_type: self::SOURCE_BINARY,
            data: $binary_data,
            display_name: $display_name,
            mime_type: $mime_type,
            file_size: strlen($binary_data),
        );
    }

    /**
     * URLからコンテンツをダウンロードしてDTOを生成
     *
     * URLからファイルをダウンロードし、バイナリデータとして内部的に保持します。
     * MIMEタイプはHTTPヘッダーから取得、取得できない場合はコンテンツから検出します。
     *
     * @param string $url URL
     * @param string $display_name 表示名
     * @param string|null $mime_type MIMEタイプ（省略時はHTTPヘッダーまたはコンテンツから検出）
     * @return self
     * @throws GeminiFileOperationException
     */
    public static function fromUrl(string $url, string $display_name, ?string $mime_type = null): self
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new GeminiFileOperationException(
                "URLからのダウンロードに失敗しました: {$url}",
                0,
                null,
                ['url' => $url]
            );
        }

        // MIMEタイプが指定されていない場合、HTTPヘッダーから取得を試みる
        if ($mime_type === null && isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^Content-Type:\s*([^;]+)/i', $header, $matches)) {
                    $mime_type = trim($matches[1]);
                    break;
                }
            }
        }

        // それでも取得できない場合はコンテンツから検出
        if ($mime_type === null) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->buffer($content);
            $mime_type = $detected ?: 'application/octet-stream';
        }

        // ダウンロードしたコンテンツをバイナリとして扱う
        return new self(
            source_type: self::SOURCE_BINARY, // URLからダウンロード済みなのでバイナリとして扱う
            data: $content,
            display_name: $display_name,
            mime_type: $mime_type,
            file_size: strlen($content),
        );
    }

    /**
     * MIMEタイプを検出
     */
    private static function detectMimeType(string $file_path): string
    {
        $mime_type = @mime_content_type($file_path);

        if ($mime_type === false || $mime_type === 'application/octet-stream' || $mime_type === null) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            $mime_type_alt = $finfo ? @finfo_file($finfo, $file_path) : false;
            if ($finfo) {
                finfo_close($finfo);
            }
            return $mime_type_alt ?: 'application/octet-stream';
        }

        return $mime_type;
    }

    public function getSourceType(): string
    {
        return $this->source_type;
    }

    public function isLocalFile(): bool
    {
        return $this->source_type === self::SOURCE_LOCAL_FILE;
    }

    public function isBinaryData(): bool
    {
        return $this->source_type === self::SOURCE_BINARY;
    }

    public function isUrl(): bool
    {
        return $this->source_type === self::SOURCE_URL;
    }

    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @deprecated Use getData() instead
     */
    public function getFile(): string
    {
        return $this->data;
    }

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function getFileSize(): int
    {
        return $this->file_size;
    }

    public function getDisplayName(): string
    {
        return $this->display_name;
    }
}
