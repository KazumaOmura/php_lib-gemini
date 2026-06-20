<?php

namespace YouCast\Gemini\NanoBanana\Template;

use YouCast\Gemini\NanoBanana\Base\AbstractPromptTemplate;

/**
 * セールスプロモーション用テンプレート
 */
class SalesPromotionTemplate extends AbstractPromptTemplate
{
    public function __construct()
    {
        $this->name = 'Sales Promotion Banner';
        $this->description = '花火背景の販促バナー用テンプレート';
        
        $this->template = <<<PROMPT
A vibrant summer sales promotional banner featuring fireworks in the background. 
The composition includes:

Main Elements:
- Spectacular fireworks display in the night sky with vivid colors ({{fireworks_colors}})
- {{product_name}} prominently displayed in the center-right area
- Energetic, festival-like atmosphere

Text Overlays (in Japanese style):
- Main campaign period: "{{campaign_date}}まで" in orange banner (top-left)
- Campaign tagline: "{{campaign_slogan}}" 
- Large bold headline: "{{main_headline}}" in white text
- Vertical text on the left: "{{vertical_text_1}}{{vertical_text_2}}{{vertical_text_3}}"
- Brand logo "{{brand_name}}" in red vertical banner (right edge)
- Product specifications: "{{product_specs}}"
- Secondary product mention: "{{secondary_product}}" in top-right
- Call-to-action: "今すぐ{{brand_name}}公式サイトへ" in red banner with arrows (bottom)

Visual Style:
- Photorealistic product rendering
- Festival/celebration atmosphere with bokeh effects
- Dark blue/purple gradient background
- High contrast between text and background
- Professional advertising photography style
- Dynamic composition with diagonal elements

Color Scheme:
- Deep blue/purple night sky
- Vibrant orange/yellow text boxes
- Bright red accent elements
- Colorful firework bursts
PROMPT;

        $this->defaultVariables = [
            'campaign_date' => '8/28',
            'campaign_slogan' => 'この夏、決断を！',
            'main_headline' => '買い替え応援サマーセール',
            'product_name' => 'ThinkPad X1 Carbon Gen 13',
            'product_specs' => 'インテル® Core™ Ultra 7プロセッサー',
            'brand_name' => 'Lenovo',
            'fireworks_colors' => 'pink, blue, orange, and golden',
            'secondary_product' => 'Lenovo IdeaCentre Mini Q WARRIOR',
            'vertical_text_1' => 'あなたに',
            'vertical_text_2' => '3大特典を',
            'vertical_text_3' => 'お見逃しなく'
        ];

        $this->requiredVariables = [
            'campaign_date',
            'main_headline',
            'product_name',
            'brand_name'
        ];
    }
}