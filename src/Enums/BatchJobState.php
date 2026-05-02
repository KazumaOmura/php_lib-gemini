<?php

namespace YouCast\Gemini\Enums;

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
     */
    public static function tryFromString(?string $state): ?self
    {
        if ($state === null) {
            return null;
        }

        return self::tryFrom($state);
    }
}
