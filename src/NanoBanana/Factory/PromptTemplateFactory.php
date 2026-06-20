<?php

namespace YouCast\Gemini\NanoBanana\Factory;

use YouCast\Gemini\NanoBanana\Contracts\PromptTemplateInterface;
use InvalidArgumentException;

/**
 * テンプレートファクトリー
 */
class PromptTemplateFactory
{
    /**
     * @var array 登録されたテンプレートクラスのマップ
     */
    private static array $templates = [];

    /**
     * テンプレートを登録
     *
     * @param string $key テンプレートキー
     * @param string $templateClass テンプレートクラス名
     * @return void
     */
    public static function register(string $key, string $templateClass): void
    {
        self::$templates[$key] = $templateClass;
    }

    /**
     * テンプレートを作成
     *
     * @param string $key テンプレートキー
     * @return PromptTemplateInterface
     * @throws InvalidArgumentException テンプレートが見つからない場合
     */
    public static function create(string $key): PromptTemplateInterface
    {
        if (!isset(self::$templates[$key])) {
            throw new InvalidArgumentException("テンプレート '{$key}' が見つかりません");
        }

        $className = self::$templates[$key];
        return new $className();
    }

    /**
     * 利用可能なテンプレートのリストを取得
     *
     * @return array テンプレートキーの配列
     */
    public static function getAvailableTemplates(): array
    {
        return array_keys(self::$templates);
    }

    /**
     * デフォルトテンプレートを登録
     *
     * @return void
     */
    public static function registerDefaults(): void
    {
        self::register('sales_promotion', \YouCast\Gemini\NanoBanana\Template\SalesPromotionTemplate::class);
    }

    /**
     * 登録されているテンプレートをクリア
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$templates = [];
    }
}