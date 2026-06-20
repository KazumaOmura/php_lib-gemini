<?php

namespace YouCast\Gemini\NanoBanana\Contracts;

/**
 * テンプレートのインターフェース
 */
interface PromptTemplateInterface
{
    /**
     * テンプレート文字列を取得
     *
     * @return string テンプレート文字列
     */
    public function getTemplate(): string;

    /**
     * デフォルト変数を取得
     *
     * @return array デフォルト変数の連想配列
     */
    public function getDefaultVariables(): array;

    /**
     * 必須変数のリストを取得
     *
     * @return array 必須変数名の配列
     */
    public function getRequiredVariables(): array;

    /**
     * テンプレート名を取得
     *
     * @return string テンプレート名
     */
    public function getName(): string;

    /**
     * テンプレートの説明を取得
     *
     * @return string テンプレートの説明
     */
    public function getDescription(): string;
}