<?php

namespace YouCast\Gemini\NanoBanana\Contracts;

/**
 * プロンプトジェネレーターのインターフェース
 */
interface PromptGeneratorInterface
{
    /**
     * プロンプトを生成
     *
     * @return string 生成されたプロンプト
     */
    public function generate(): string;

    /**
     * 変数を設定
     *
     * @param string $key 変数名
     * @param string $value 変数の値
     * @return self
     */
    public function setVariable(string $key, string $value): self;

    /**
     * 複数の変数を一括設定
     *
     * @param array $variables 変数の連想配列
     * @return self
     */
    public function setVariables(array $variables): self;

    /**
     * 変数を取得
     *
     * @param string $key 変数名
     * @return string|null 変数の値、存在しない場合はnull
     */
    public function getVariable(string $key): ?string;

    /**
     * 全変数を取得
     *
     * @return array 全変数の連想配列
     */
    public function getAllVariables(): array;

    /**
     * テンプレートを検証
     *
     * @return bool 検証結果
     */
    public function validate(): bool;
}