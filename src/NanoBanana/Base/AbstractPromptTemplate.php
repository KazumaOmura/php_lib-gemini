<?php

namespace YouCast\Gemini\NanoBanana\Base;

use YouCast\Gemini\NanoBanana\Contracts\PromptTemplateInterface;

/**
 * 抽象テンプレートクラス
 */
abstract class AbstractPromptTemplate implements PromptTemplateInterface
{
    /**
     * @var string テンプレート名
     */
    protected string $name;

    /**
     * @var string テンプレートの説明
     */
    protected string $description;

    /**
     * @var string テンプレート文字列
     */
    protected string $template;

    /**
     * @var array デフォルト変数
     */
    protected array $defaultVariables = [];

    /**
     * @var array 必須変数
     */
    protected array $requiredVariables = [];

    /**
     * {@inheritdoc}
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultVariables(): array
    {
        return $this->defaultVariables;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredVariables(): array
    {
        return $this->requiredVariables;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}