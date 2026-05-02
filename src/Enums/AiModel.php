<?php

namespace YouCast\Gemini\Enums;

use InvalidArgumentException;

enum AiModel: string
{
    case GEMINI_3_PRO = 'gemini-3-pro-preview';
    case GEMINI_3_FLASH = 'gemini-3-flash-preview';
    case GEMINI_2_5_PRO = 'gemini-2.5-pro';
    case GEMINI_2_5_FLASH = 'gemini-2.5-flash';
    case GEMINI_2_0_FLASH = 'gemini-2.0-flash';

    private const BASE_URL = 'https://generativelanguage.googleapis.com';
    private const API_VERSION = 'v1beta';

    /**
     * モデル名を取得（models/xxx形式）
     */
    public function getModelName(): string
    {
        return 'models/' . $this->value;
    }

    /**
     * generateContent API URL（リアルタイム）
     */
    public function getGenerateContentUrl(): string
    {
        return self::BASE_URL . '/' . self::API_VERSION . '/models/' . $this->value . ':generateContent';
    }

    /**
     * batchGenerateContent API URL（バッチジョブ作成）
     * POST /v1beta/models/{model}:batchGenerateContent
     */
    public function getBatchGenerateContentUrl(): string
    {
        return self::BASE_URL . '/' . self::API_VERSION . '/models/' . $this->value . ':batchGenerateContent';
    }

    /**
     * バッチジョブ管理API URL（一覧取得・状態確認・キャンセル・削除）
     * GET/POST/DELETE /v1beta/batches/{batch_name}
     */
    public static function getBatchesUrl(): string
    {
        return self::BASE_URL . '/' . self::API_VERSION . '/batches';
    }

    /**
     * File API URL（アップロード用）
     */
    public static function getFileUploadUrl(): string
    {
        return self::BASE_URL . '/upload/' . self::API_VERSION . '/files';
    }

    /**
     * File API URL（操作用）
     */
    public static function getFilesUrl(): string
    {
        return self::BASE_URL . '/' . self::API_VERSION . '/files';
    }

    /**
     * ファイルダウンロードURL
     */
    public static function getFileDownloadUrl(string $file_name): string
    {
        return self::BASE_URL . '/download/' . self::API_VERSION . '/' . $file_name . ':download';
    }

    public static function fromString(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Invalid ai model: {$value}");
    }
}
