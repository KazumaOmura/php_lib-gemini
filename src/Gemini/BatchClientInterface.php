<?php

namespace YouCast\Gemini\Gemini;

use YouCast\Gemini\Gemini\Dto\BatchResponseDto;
use YouCast\Gemini\Gemini\Dto\GenerationConfigDto;

/**
 * バッチAPIクライアントの共通インターフェース
 */
interface BatchClientInterface
{
    /**
     * Safety Settingsを設定する
     *
     * @param array $safety_settings
     * @return self
     */
    public function setSafetySettings(array $safety_settings): self;

    /**
     * Generation Configを設定する
     *
     * @param GenerationConfigDto $generation_config
     * @return self
     */
    public function setGenerationConfig(GenerationConfigDto $generation_config): self;

    /**
     * バッチジョブを作成
     *
     * @param array $requests リクエスト配列 [['prompt' => '...', 'inline_data' => [...], 'key' => '...'], ...]
     * @param string|null $display_name 表示名
     * @return BatchResponseDto
     */
    public function create(array $requests, ?string $display_name = null): BatchResponseDto;

    /**
     * バッチジョブのステータスを取得
     *
     * @param string $batch_name バッチ名
     * @return BatchResponseDto
     */
    public function get(string $batch_name): BatchResponseDto;

    /**
     * バッチジョブ一覧を取得
     *
     * @param int $page_size ページサイズ
     * @param string|null $page_token ページトークン
     * @return array{batches: BatchResponseDto[], next_page_token: string|null}
     */
    public function list(int $page_size = 100, ?string $page_token = null): array;

    /**
     * バッチジョブをキャンセル
     *
     * @param string $batch_name バッチ名
     * @return BatchResponseDto
     */
    public function cancel(string $batch_name): BatchResponseDto;

    /**
     * バッチジョブを削除
     *
     * @param string $batch_name バッチ名
     * @return bool
     */
    public function delete(string $batch_name): bool;
}
