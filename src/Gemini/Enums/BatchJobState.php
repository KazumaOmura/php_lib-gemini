<?php

namespace YouCast\Gemini\Gemini\Enums;

/**
 * Gemini Batch API ジョブ状態
 */
enum BatchJobState: string
{
    case PENDING = 'BATCH_STATE_PENDING';
    case RUNNING = 'BATCH_STATE_RUNNING';
    case SUCCEEDED = 'BATCH_STATE_SUCCEEDED';
    case FAILED = 'BATCH_STATE_FAILED';
    case CANCELLED = 'BATCH_STATE_CANCELLED';

    /**
     * 完了状態かどうか（成功・失敗・キャンセル）
     */
    public function isCompleted(): bool
    {
        return in_array($this, [
            self::SUCCEEDED,
            self::FAILED,
            self::CANCELLED,
        ]);
    }

    /**
     * 処理中かどうか（待機中・実行中）
     */
    public function isProcessing(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::RUNNING,
        ]);
    }

    /**
     * 成功したかどうか
     */
    public function isSucceeded(): bool
    {
        return $this === self::SUCCEEDED;
    }

    /**
     * 失敗したかどうか
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * 文字列からEnumを取得（不明な状態はnull）
     *
     * Gemini API: BATCH_STATE_PENDING, BATCH_STATE_RUNNING, etc.
     * Vertex AI:  JOB_STATE_PENDING, JOB_STATE_RUNNING, JOB_STATE_SUCCEEDED, etc.
     */
    public static function tryFromString(?string $state): ?self
    {
        if ($state === null) {
            return null;
        }

        // まず直接マッチを試みる（Gemini API形式）
        $result = self::tryFrom($state);
        if ($result !== null) {
            return $result;
        }

        // Vertex AI形式のマッピング
        return match ($state) {
            'JOB_STATE_QUEUED', 'JOB_STATE_PENDING' => self::PENDING,
            'JOB_STATE_RUNNING' => self::RUNNING,
            'JOB_STATE_SUCCEEDED' => self::SUCCEEDED,
            'JOB_STATE_FAILED', 'JOB_STATE_PARTIALLY_SUCCEEDED' => self::FAILED,
            'JOB_STATE_CANCELLING', 'JOB_STATE_CANCELLED' => self::CANCELLED,
            default => null,
        };
    }
}
