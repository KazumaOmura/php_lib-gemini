<?php

namespace YouCast\Gemini\NanoBanana\Base;

use YouCast\Gemini\NanoBanana\Contracts\PromptGeneratorInterface;
use YouCast\Gemini\NanoBanana\Contracts\PromptTemplateInterface;
use RuntimeException;

/**
 * 抽象プロンプトジェネレータークラス
 */
abstract class AbstractPromptGenerator implements PromptGeneratorInterface
{
    /**
     * @var array 変数の連想配列
     */
    protected array $variables = [];

    /**
     * @var PromptTemplateInterface テンプレート
     */
    protected PromptTemplateInterface $template;

    /**
     * コンストラクタ
     *
     * @param PromptTemplateInterface $template テンプレート
     */
    public function __construct(PromptTemplateInterface $template)
    {
        $this->template = $template;
        $this->variables = $template->getDefaultVariables();
    }

    /**
     * {@inheritdoc}
     */
    public function setVariable(string $key, string $value): self
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setVariables(array $variables): self
    {
        $this->variables = array_merge($this->variables, $variables);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariable(string $key): ?string
    {
        return $this->variables[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllVariables(): array
    {
        return $this->variables;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): bool
    {
        $requiredVariables = $this->template->getRequiredVariables();
        
        foreach ($requiredVariables as $required) {
            if (!isset($this->variables[$required]) || empty($this->variables[$required])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        if (!$this->validate()) {
            throw new RuntimeException('必須変数が設定されていません');
        }

        return $this->replaceVariables($this->template->getTemplate());
    }

    /**
     * テンプレート内の変数を置換
     *
     * @param string $template テンプレート文字列
     * @return string 変数が置換されたテンプレート
     */
    protected function replaceVariables(string $template): string
    {
        foreach ($this->variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $template = str_replace($placeholder, $value, $template);
        }
        
        return $template;
    }

    /**
     * テンプレート名を取得
     *
     * @return string テンプレート名
     */
    public function getTemplateName(): string
    {
        return $this->template->getName();
    }

    /**
     * テンプレートの説明を取得
     *
     * @return string テンプレートの説明
     */
    public function getTemplateDescription(): string
    {
        return $this->template->getDescription();
    }

    /**
     * 変数をリセット
     *
     * @return self
     */
    public function resetVariables(): self
    {
        $this->variables = $this->template->getDefaultVariables();
        return $this;
    }
}