<?php

namespace YouCast\Gemini\Gemini\Enums;

use InvalidArgumentException;

enum AiModel: string
{
    // Gemini 3.x Flash 系（GA / stable）
    case GEMINI_3_7_FLASH = 'gemini-3.7-flash';       // 最新（2026-08-13 GA）
    case GEMINI_3_6_FLASH = 'gemini-3.6-flash';       // 2026-07-21 GA
    case GEMINI_3_5_FLASH = 'gemini-3.5-flash';       // 2026-05-19 GA
    case GEMINI_3_5_FLASH_LITE = 'gemini-3.5-flash-lite'; // 2026-07-21 GA
    case GEMINI_3_1_FLASH_LITE = 'gemini-3.1-flash-lite'; // 2026-05-07 GA

    // Gemini 3.x Pro / Flash 系（preview）
    case GEMINI_3_1_PRO = 'gemini-3.1-pro-preview';
    case GEMINI_3_FLASH = 'gemini-3-flash-preview';

    // Gemini 2.5 系（GA）
    case GEMINI_2_5_PRO = 'gemini-2.5-pro';
    case GEMINI_2_5_FLASH = 'gemini-2.5-flash';

    /** @deprecated 2026年に提供終了（Shut down）。後方互換のため残置。 */
    case GEMINI_3_PRO = 'gemini-3-pro-preview';
    /** @deprecated 2026-06-01 に提供終了（Shut down）。後方互換のため残置。 */
    case GEMINI_2_0_FLASH = 'gemini-2.0-flash';

    // TTS (Text-to-Speech) モデル
    case GEMINI_3_1_FLASH_TTS = 'gemini-3.1-flash-tts-preview';
    case GEMINI_2_5_FLASH_TTS = 'gemini-2.5-flash-preview-tts';
    case GEMINI_2_5_PRO_TTS = 'gemini-2.5-pro-preview-tts';

    /**
     * TTS (音声生成) 用モデルかどうか
     */
    public function isTts(): bool
    {
        return match ($this) {
            self::GEMINI_3_1_FLASH_TTS,
            self::GEMINI_2_5_FLASH_TTS,
            self::GEMINI_2_5_PRO_TTS => true,
            default => false,
        };
    }

    private const BASE_URL = 'https://generativelanguage.googleapis.com';
    private const API_VERSION = 'v1beta';
    private const VERTEX_AI_API_VERSION = 'v1';

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

    /**
     * Vertex AI のベースURLを取得
     *
     * globalの場合: https://aiplatform.googleapis.com
     * リージョナルの場合: https://{location}-aiplatform.googleapis.com
     */
    public static function getVertexAiBaseUrl(string $project_id, string $location): string
    {
        $host = $location === 'global'
            ? 'https://aiplatform.googleapis.com'
            : 'https://' . $location . '-aiplatform.googleapis.com';

        return $host . '/' . self::VERTEX_AI_API_VERSION . '/projects/' . $project_id . '/locations/' . $location;
    }

    /**
     * Vertex AI batchGenerateContent API URL
     */
    public function getVertexAiBatchGenerateContentUrl(string $project_id, string $location): string
    {
        return self::getVertexAiBaseUrl($project_id, $location) . '/publishers/google/models/' . $this->value . ':batchGenerateContent';
    }

    /**
     * Vertex AI バッチジョブ管理API URL
     */
    public static function getVertexAiBatchesUrl(string $project_id, string $location): string
    {
        return self::getVertexAiBaseUrl($project_id, $location) . '/batchPredictionJobs';
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
