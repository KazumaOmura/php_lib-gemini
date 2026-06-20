<?php

namespace YouCast\Gemini\NanoBanana\Builders;

/**
 * フォトリアリスティックな画像生成のためのプロンプトビルダークラス
 */
class PhotographyPromptBuilder
{
    private string $subject = '';
    private string $camera_angle = '';
    private string $lens_type = '';
    private string $lighting = '';
    private string $mood = '';
    private string $background = '';
    private string $style = '';
    private array $details = [];
    private string $quality = '';

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
     * カメラアングルを設定する
     *
     * @param string $angle
     * @return self
     */
    public function setCameraAngle(string $angle): self
    {
        $this->camera_angle = $angle;
        return $this;
    }

    /**
     * レンズの種類を設定する
     *
     * @param string $lens
     * @return self
     */
    public function setLensType(string $lens): self
    {
        $this->lens_type = $lens;
        return $this;
    }

    /**
     * 照明を設定する
     *
     * @param string $lighting
     * @return self
     */
    public function setLighting(string $lighting): self
    {
        $this->lighting = $lighting;
        return $this;
    }

    /**
     * ムードを設定する
     *
     * @param string $mood
     * @return self
     */
    public function setMood(string $mood): self
    {
        $this->mood = $mood;
        return $this;
    }

    /**
     * 背景を設定する
     *
     * @param string $background
     * @return self
     */
    public function setBackground(string $background): self
    {
        $this->background = $background;
        return $this;
    }

    /**
     * スタイルを設定する
     *
     * @param string $style
     * @return self
     */
    public function setStyle(string $style): self
    {
        $this->style = $style;
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
     * 品質を設定する
     *
     * @param string $quality
     * @return self
     */
    public function setQuality(string $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * プロンプトを生成する
     *
     * @return string
     */
    public function build(): string
    {
        $prompt_parts = [];

        // 基本設定
        if (!empty($this->subject)) {
            $prompt_parts[] = "A photorealistic {$this->subject}";
        }

        // カメラアングル
        if (!empty($this->camera_angle)) {
            $prompt_parts[] = "shot with a {$this->camera_angle}";
        }

        // レンズの種類
        if (!empty($this->lens_type)) {
            $prompt_parts[] = "using a {$this->lens_type}";
        }

        // 照明
        if (!empty($this->lighting)) {
            $prompt_parts[] = "illuminated by {$this->lighting}";
        }

        // 背景
        if (!empty($this->background)) {
            $prompt_parts[] = "with {$this->background} in the background";
        }

        // 詳細
        if (!empty($this->details)) {
            $prompt_parts[] = implode('. ', $this->details);
        }

        // ムード
        if (!empty($this->mood)) {
            $prompt_parts[] = "The overall mood is {$this->mood}";
        }

        // スタイル
        if (!empty($this->style)) {
            $prompt_parts[] = "Style: {$this->style}";
        }

        // 品質
        if (!empty($this->quality)) {
            $prompt_parts[] = "Quality: {$this->quality}";
        }

        return implode('. ', $prompt_parts) . '.';
    }

    /**
     * プリセットを適用する
     *
     * @param string $preset_name
     * @return self
     */
    public function applyPreset(string $preset_name): self
    {
        switch ($preset_name) {
            case 'portrait':
                $this->setCameraAngle('close-up portrait')
                     ->setLensType('85mm portrait lens')
                     ->setLighting('soft, golden hour light')
                     ->setMood('serene and masterful')
                     ->setQuality('high resolution, professional photography');
                break;

            case 'landscape':
                $this->setCameraAngle('wide-angle landscape shot')
                     ->setLensType('24-70mm wide-angle lens')
                     ->setLighting('natural daylight')
                     ->setMood('dramatic and breathtaking')
                     ->setQuality('ultra-high resolution, National Geographic style');
                break;

            case 'macro':
                $this->setCameraAngle('extreme close-up macro shot')
                     ->setLensType('100mm macro lens')
                     ->setLighting('soft, diffused lighting')
                     ->setMood('intimate and detailed')
                     ->setQuality('crystal clear, macro photography');
                break;

            case 'street':
                $this->setCameraAngle('candid street photography')
                     ->setLensType('35mm prime lens')
                     ->setLighting('natural urban lighting')
                     ->setMood('authentic and raw')
                     ->setQuality('documentary style, black and white or color');
                break;

            case 'studio':
                $this->setCameraAngle('professional studio shot')
                     ->setLensType('50mm prime lens')
                     ->setLighting('controlled studio lighting with softbox')
                     ->setMood('clean and professional')
                     ->setQuality('commercial photography quality');
                break;
        }

        return $this;
    }

    /**
     * リセットする
     *
     * @return self
     */
    public function reset(): self
    {
        $this->subject = '';
        $this->camera_angle = '';
        $this->lens_type = '';
        $this->lighting = '';
        $this->mood = '';
        $this->background = '';
        $this->style = '';
        $this->details = [];
        $this->quality = '';

        return $this;
    }
}
