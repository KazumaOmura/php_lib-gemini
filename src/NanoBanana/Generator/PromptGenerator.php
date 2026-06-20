<?php

namespace YouCast\Gemini\NanoBanana\Generator;

use YouCast\Gemini\NanoBanana\Base\AbstractPromptGenerator;

/**
 * 標準プロンプトジェネレータークラス
 */
class PromptGenerator extends AbstractPromptGenerator
{
    /**
     * JSONとして変数をエクスポート
     *
     * @return string JSON形式の変数データ
     */
    public function exportVariablesAsJson(): string
    {
        return json_encode($this->variables, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * JSONから変数をインポート
     *
     * @param string $json JSON形式の変数データ
     * @return self
     */
    public function importVariablesFromJson(string $json): self
    {
        $variables = json_decode($json, true);
        if (is_array($variables)) {
            $this->setVariables($variables);
        }
        return $this;
    }

    /**
     * 不足している必須変数を取得
     *
     * @return array 不足している必須変数名の配列
     */
    public function getMissingRequiredVariables(): array
    {
        $requiredVariables = $this->template->getRequiredVariables();
        $missing = [];
        
        foreach ($requiredVariables as $required) {
            if (!isset($this->variables[$required]) || empty($this->variables[$required])) {
                $missing[] = $required;
            }
        }
        
        return $missing;
    }

    /**
     * テンプレート変数の一覧を表示
     *
     * @return array テンプレート情報の連想配列
     */
    public function showVariableInfo(): array
    {
        return [
            'template_name' => $this->getTemplateName(),
            'description' => $this->getTemplateDescription(),
            'required_variables' => $this->template->getRequiredVariables(),
            'current_variables' => $this->variables,
            'missing_variables' => $this->getMissingRequiredVariables()
        ];
    }
}