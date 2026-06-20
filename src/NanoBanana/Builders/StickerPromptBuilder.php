<?php

namespace YouCast\Gemini\NanoBanana\Builders;

/**
 * ステッカー生成のためのプロンプトビルダークラス
 */
class StickerPromptBuilder
{
    private string $subject = '';
    private string $style = 'kawaii';
    private string $background = 'transparent';
    private string $outline = 'bold';
    private string $shading = 'cel-shading';
    private string $color_palette = 'vibrant';
    private string $size = 'medium';
    private array $details = [];
    private string $mood = 'cheerful';

    /**
     * 被写体を設定する
     *
     * @param string $subject
     * @return self
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * スタイルを設定する
     *
     * @param string $style kawaii, minimalist, vintage, cartoon, anime
     * @return self
     */
    public function setStyle(string $style): self
    {
        $this->style = $style;
        return $this;
    }

    /**
     * 背景を設定する
     *
     * @param string $background transparent, white, color, gradient
     * @return self
     */
    public function setBackground(string $background): self
    {
        $this->background = $background;
        return $this;
    }

    /**
     * アウトラインを設定する
     *
     * @param string $outline bold, thin, medium, none
     * @return self
     */
    public function setOutline(string $outline): self
    {
        $this->outline = $outline;
        return $this;
    }

    /**
     * シェーディングを設定する
     *
     * @param string $shading cel-shading, flat, gradient, soft
     * @return self
     */
    public function setShading(string $shading): self
    {
        $this->shading = $shading;
        return $this;
    }

    /**
     * カラーパレットを設定する
     *
     * @param string $color_palette vibrant, pastel, monochrome, earth-tone, neon
     * @return self
     */
    public function setColorPalette(string $color_palette): self
    {
        $this->color_palette = $color_palette;
        return $this;
    }

    /**
     * サイズを設定する
     *
     * @param string $size small, medium, large, xlarge
     * @return self
     */
    public function setSize(string $size): self
    {
        $this->size = $size;
        return $this;
    }

    /**
     * ムードを設定する
     *
     * @param string $mood cheerful, serious, playful, mysterious, cute
     * @return self
     */
    public function setMood(string $mood): self
    {
        $this->mood = $mood;
        return $this;
    }

    /**
     * 詳細を追加する
     *
     * @param string $detail
     * @return self
     */
    public function addDetail(string $detail): self
    {
        $this->details[] = $detail;
        return $this;
    }

    /**
     * 複数の詳細を設定する
     *
     * @param array $details
     * @return self
     */
    public function setDetails(array $details): self
    {
        $this->details = $details;
        return $this;
    }

    /**
     * プリセットを適用する
     *
     * @param string $preset kawaii, minimalist, vintage, professional, playful
     * @return self
     */
    public function applyPreset(string $preset): self
    {
        switch ($preset) {
            case 'kawaii':
                $this->style = 'kawaii';
                $this->background = 'transparent';
                $this->outline = 'bold';
                $this->shading = 'cel-shading';
                $this->color_palette = 'vibrant';
                $this->mood = 'cute';
                break;
            case 'minimalist':
                $this->style = 'minimalist';
                $this->background = 'transparent';
                $this->outline = 'thin';
                $this->shading = 'flat';
                $this->color_palette = 'monochrome';
                $this->mood = 'serious';
                break;
            case 'vintage':
                $this->style = 'vintage';
                $this->background = 'transparent';
                $this->outline = 'medium';
                $this->shading = 'soft';
                $this->color_palette = 'earth-tone';
                $this->mood = 'serious';
                break;
            case 'professional':
                $this->style = 'minimalist';
                $this->background = 'transparent';
                $this->outline = 'medium';
                $this->shading = 'flat';
                $this->color_palette = 'monochrome';
                $this->mood = 'serious';
                break;
            case 'playful':
                $this->style = 'cartoon';
                $this->background = 'transparent';
                $this->outline = 'bold';
                $this->shading = 'cel-shading';
                $this->color_palette = 'vibrant';
                $this->mood = 'playful';
                break;
        }
        return $this;
    }

    /**
     * プロンプトを構築する
     *
     * @return string
     */
    public function build(): string
    {
        if (empty($this->subject)) {
            throw new \InvalidArgumentException('被写体が設定されていません。');
        }

        $prompt = "A {$this->style}-style sticker of {$this->subject}. ";
        
        // スタイルの詳細を追加
        $this->addStyleDetails($prompt);
        
        // ムードの追加
        $prompt .= "The overall mood should be {$this->mood}. ";
        
        // アウトラインとシェーディング
        if ($this->outline !== 'none') {
            $prompt .= "It has {$this->outline}, clean outlines and {$this->shading}. ";
        } else {
            $prompt .= "It has {$this->shading} without outlines. ";
        }
        
        // カラーパレット
        $prompt .= "Use a {$this->color_palette} color palette. ";
        
        // 背景の指定
        $this->addBackgroundDetails($prompt);
        
        // サイズの指定
        $prompt .= "The sticker should be {$this->size} size and suitable for use as a digital sticker, icon, or asset. ";
        
        // 詳細の追加
        if (!empty($this->details)) {
            $prompt .= "Additional details: " . implode(', ', $this->details) . ". ";
        }

        return trim($prompt);
    }

    /**
     * スタイルの詳細を追加する
     *
     * @param string &$prompt
     * @return void
     */
    private function addStyleDetails(string &$prompt): void
    {
        switch ($this->style) {
            case 'kawaii':
                $prompt .= "The design features cute, rounded features with big expressive eyes. ";
                break;
            case 'minimalist':
                $prompt .= "The design features clean, simple lines with minimal details. ";
                break;
            case 'vintage':
                $prompt .= "The design features retro styling with classic color schemes. ";
                break;
            case 'cartoon':
                $prompt .= "The design features cartoon-style with simplified but expressive design. ";
                break;
            case 'anime':
                $prompt .= "The design features anime/manga art style with detailed character design. ";
                break;
        }
    }

    /**
     * 背景の詳細を追加する
     *
     * @param string &$prompt
     * @return void
     */
    private function addBackgroundDetails(string &$prompt): void
    {
        switch ($this->background) {
            case 'transparent':
                $prompt .= "The background must be transparent. ";
                break;
            case 'white':
                $prompt .= "The background should be white. ";
                break;
            case 'color':
                $prompt .= "The background should be a solid color that complements the subject. ";
                break;
            case 'gradient':
                $prompt .= "The background should be a subtle gradient. ";
                break;
        }
    }

    /**
     * 現在の設定を取得する
     *
     * @return array
     */
    public function getSettings(): array
    {
        return [
            'subject' => $this->subject,
            'style' => $this->style,
            'background' => $this->background,
            'outline' => $this->outline,
            'shading' => $this->shading,
            'color_palette' => $this->color_palette,
            'size' => $this->size,
            'mood' => $this->mood,
            'details' => $this->details
        ];
    }

    /**
     * 設定をリセットする
     *
     * @return self
     */
    public function reset(): self
    {
        $this->subject = '';
        $this->style = 'kawaii';
        $this->background = 'transparent';
        $this->outline = 'bold';
        $this->shading = 'cel-shading';
        $this->color_palette = 'vibrant';
        $this->size = 'medium';
        $this->details = [];
        $this->mood = 'cheerful';
        return $this;
    }
}
