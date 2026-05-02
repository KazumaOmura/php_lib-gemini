<?php

namespace YouCast\Gemini\Dto;

use YouCast\Gemini\Enums\BatchJobState;

/**
 * Gemini Batch API レスポンス DTO
 */
class BatchResponseDto
{
    private string $name;
    private ?BatchJobState $state;
    private ?string $model;
    private ?string $display_name;
    private ?string $create_time;
    private ?string $update_time;
    private array $batch_stats;
    private bool $done;
    private ?array $error;
    private ?array $response;
    private ?array $output;
    private array $raw_response;

    /** @var BatchInlinedResponseDto[] */
    private array $inlined_response_dtos = [];

    public function __construct(array $raw_response)
    {
        $this->raw_response = $raw_response;
        $this->name = $raw_response['name'] ?? '';
        $this->done = $raw_response['done'] ?? false;
        $this->error = $raw_response['error'] ?? null;
        $this->response = $raw_response['response'] ?? null;

        // メタデータからフィールドを取得
        $metadata = $raw_response['metadata'] ?? [];
        $this->model = $metadata['model'] ?? null;
        $this->display_name = $metadata['displayName'] ?? null;
        $this->create_time = $metadata['createTime'] ?? null;
        $this->update_time = $metadata['updateTime'] ?? null;
        $this->state = BatchJobState::tryFromString($metadata['state'] ?? null);
        $this->batch_stats = $metadata['batchStats'] ?? [];
        $this->output = $metadata['output'] ?? null;

        // インライン結果をDTOに変換
        $this->parseInlinedResponses();
    }

    /**
     * インライン結果をパースしてDTOに変換
     */
    private function parseInlinedResponses(): void
    {
        // response.inlinedResponses.inlinedResponses[] を優先（完了時）
        // なければ metadata.output.inlinedResponses.inlinedResponses[] を使用
        $inlined_responses = $this->response['inlinedResponses']['inlinedResponses']
            ?? $this->output['inlinedResponses']['inlinedResponses']
            ?? [];

        foreach ($inlined_responses as $response) {
            $this->inlined_response_dtos[] = new BatchInlinedResponseDto($response);
        }
    }

    /**
     * バッチ名（batches/xxx形式）
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * ジョブ状態
     */
    public function getState(): ?BatchJobState
    {
        return $this->state;
    }

    /**
     * ジョブ状態（文字列）
     */
    public function getStateString(): ?string
    {
        return $this->state?->value;
    }

    /**
     * モデル名
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * 表示名
     */
    public function getDisplayName(): ?string
    {
        return $this->display_name;
    }

    /**
     * 作成日時
     */
    public function getCreateTime(): ?string
    {
        return $this->create_time;
    }

    /**
     * 更新日時
     */
    public function getUpdateTime(): ?string
    {
        return $this->update_time;
    }

    /**
     * バッチ統計情報
     */
    public function getBatchStats(): array
    {
        return $this->batch_stats;
    }

    /**
     * ジョブが完了したか（done フラグ）
     */
    public function isDone(): bool
    {
        return $this->done;
    }

    /**
     * エラー情報
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    /**
     * レスポンス（完了時）
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }

    /**
     * インライン結果DTOを取得
     *
     * @return BatchInlinedResponseDto[]
     */
    public function getInlinedResponses(): array
    {
        return $this->inlined_response_dtos;
    }

    /**
     * インライン結果を生配列で取得（後方互換性用）
     */
    public function getInlinedResponsesRaw(): array
    {
        return $this->output['inlinedResponses']['inlinedResponses'] ?? [];
    }

    /**
     * キーでインライン結果を検索
     *
     * @param string $key
     * @return BatchInlinedResponseDto|null
     */
    public function findInlinedResponseByKey(string $key): ?BatchInlinedResponseDto
    {
        foreach ($this->inlined_response_dtos as $dto) {
            if ($dto->getKey() === $key) {
                return $dto;
            }
        }
        return null;
    }

    /**
     * インデックスでインライン結果を取得
     *
     * @param int $index
     * @return BatchInlinedResponseDto|null
     */
    public function getInlinedResponseByIndex(int $index): ?BatchInlinedResponseDto
    {
        return $this->inlined_response_dtos[$index] ?? null;
    }

    /**
     * 結果ファイル名を取得
     */
    public function getResponsesFile(): ?string
    {
        return $this->output['responsesFile'] ?? null;
    }

    /**
     * 完了状態かどうか
     */
    public function isCompleted(): bool
    {
        return $this->state?->isCompleted() ?? false;
    }

    /**
     * 処理中かどうか
     */
    public function isProcessing(): bool
    {
        return $this->state?->isProcessing() ?? false;
    }

    /**
     * 成功したかどうか
     */
    public function isSucceeded(): bool
    {
        return $this->state?->isSucceeded() ?? false;
    }

    /**
     * 失敗したかどうか
     */
    public function isFailed(): bool
    {
        return $this->state?->isFailed() ?? false;
    }

    /**
     * 生レスポンス
     */
    public function getRawResponse(): array
    {
        return $this->raw_response;
    }

    /**
     * 配列として返す
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'state' => $this->getStateString(),
            'model' => $this->model,
            'display_name' => $this->display_name,
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
            'batch_stats' => $this->batch_stats,
            'done' => $this->done,
            'error' => $this->error,
            'response' => $this->response,
        ];
    }
}
