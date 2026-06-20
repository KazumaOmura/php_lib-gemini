# NanoBanana

Google Gemini Image 系（Nano Banana / Nano Banana Pro）のクライアント。テキスト→画像生成、画像編集、ステッカー/フォトリアルなど用途特化のヘルパーを提供する。

## サポートモデル (`ImageModel`)

| ケース | モデル名 | 通称 |
|---|---|---|
| `GEMINI_2_5_FLASH_IMAGE` | `gemini-2.5-flash-image` | Nano Banana |
| `GEMINI_3_PRO_IMAGE_PREVIEW` | `gemini-3-pro-image-preview` | **Nano Banana Pro** |
| `GEMINI_3_1_FLASH_IMAGE_PREVIEW` | `gemini-3.1-flash-image-preview` | Nano Banana 3.1 Flash |

> 通称：Nano Banana = Gemini の画像生成系全般、Nano Banana Pro = Gemini 3 Pro Image Preview。

## 注意：HTTPクライアント

`NanoBananaClient` は元ライブラリの仕様を踏襲し `Illuminate\Support\Facades\Http` を内部で利用します。Laravel アプリケーション内、もしくは `Illuminate\Support\Facades\Facade::setFacadeApplication()` で Facade をブートストラップ済みの環境で利用してください。

---

## ユースケース1: シンプルな画像生成

テキストプロンプトから1枚生成して保存。

```php
use YouCast\Gemini\NanoBanana\NanoBananaClient;
use YouCast\Gemini\NanoBanana\Enums\ImageModel;

$client = new NanoBananaClient(
    api_key: getenv('GEMINI_API_KEY'),
    model:   ImageModel::GEMINI_3_PRO_IMAGE_PREVIEW,  // Nano Banana Pro
);

$response = $client->generateImage(
    prompt: '夕焼けの東京タワー、エモーショナルな構図、シネマティック',
    output_path: __DIR__ . '/output/tokyo_tower.png',
);

echo "使用トークン: " . $response->getTotalTokenCount() . PHP_EOL;
```

---

## ユースケース2: 画像編集（テキスト + 既存画像）

既存画像をベースに加筆指示を出す。ローカルパス・リモートURL両方OK。

```php
$response = $client->editImage(
    prompt: 'この写真の背景をきらびやかな夜景に置き換えて、人物の表情はそのままに',
    input_image_paths: [
        '/path/to/portrait.jpg',
        'https://example.com/reference.png',
    ],
    output_path: __DIR__ . '/output/edited.png',
);
```

最大10枚まで参照画像を渡せます。

---

## ユースケース3: フォトリアリスティック画像（写真用語で細かく指定）

カメラアングル、レンズ、ライティング等の写真用語を構造化して渡す。

```php
$response = $client->generatePhotorealisticImage(
    subject: 'a golden retriever puppy sitting in a meadow',
    output_path: __DIR__ . '/output/puppy.png',
    photography_params: [
        'camera_angle' => 'low angle, eye level',
        'lens_type'    => '85mm f/1.4 prime lens',
        'lighting'     => 'golden hour, warm sunlight',
        'mood'         => 'peaceful, joyful',
        'background'   => 'soft bokeh meadow with wildflowers',
        'style'        => 'National Geographic style',
        'details'      => ['shallow depth of field', 'natural fur texture'],
        'quality'      => 'ultra high resolution, sharp focus',
    ],
);
```

### プリセットを使う

`portrait`, `landscape`, `macro`, `street`, `studio` のプリセットを適用。

```php
$response = $client->generatePhotorealisticImageWithPreset(
    subject: 'a Japanese ramen chef',
    output_path: __DIR__ . '/output/chef.png',
    preset: 'portrait',
    additional_params: [
        'mood' => 'intense, focused',
    ],
);
```

---

## ユースケース4: ステッカー生成

LINE スタンプ風や SNS 用のスタンプを生成。

```php
$response = $client->generateSticker(
    subject: 'a cute shiba inu wearing sunglasses',
    output_path: __DIR__ . '/output/shiba_sticker.png',
    sticker_params: [
        'style'         => 'kawaii',           // kawaii / minimalist / vintage / cartoon / anime
        'background'    => 'transparent',      // transparent / white / color / gradient
        'outline'       => 'bold',             // bold / thin / medium / none
        'shading'       => 'cel-shading',      // cel-shading / flat / gradient / soft
        'color_palette' => 'vibrant',          // vibrant / pastel / monochrome / earth-tone / neon
        'size'          => 'medium',           // small / medium / large / xlarge
        'mood'          => 'playful',
        'details'       => ['cool expression', 'thumbs up pose'],
    ],
);
```

`preset` で一括指定も可能（`kawaii` / `minimalist` / `vintage` / `professional` / `playful`）。

```php
$client->generateSticker(
    subject: 'a sleeping cat',
    output_path: __DIR__ . '/output/cat_sticker.png',
    sticker_params: ['preset' => 'kawaii'],
);
```

---

## ユースケース5: Builder で自前のプロンプトを組む

ヘルパーメソッドではなく `PromptBuilder` を直接使ってプロンプトを生成し、`generateImage()` に渡す。

```php
use YouCast\Gemini\NanoBanana\Builders\PhotographyPromptBuilder;

$prompt = (new PhotographyPromptBuilder())
    ->setSubject('a vintage red typewriter on an old wooden desk')
    ->setLighting('soft window light from the left')
    ->setMood('nostalgic, quiet morning')
    ->applyPreset('studio')
    ->addDetail('dust particles in the light beam')
    ->build();

$client->generateImage($prompt, __DIR__ . '/output/typewriter.png');
```

ステッカー用 `StickerPromptBuilder` も同じ流暢インターフェース。

---

## ユースケース6: テンプレートを使って動的にプロンプト生成

販促バナーなどの定型用途は `PromptTemplate` + `PromptGenerator` で変数差し替え。

```php
use YouCast\Gemini\NanoBanana\Generator\PromptGenerator;
use YouCast\Gemini\NanoBanana\Template\SalesPromotionTemplate;

$generator = new PromptGenerator(new SalesPromotionTemplate());
$generator
    ->setVariable('product_name', 'プレミアム抹茶ラテ')
    ->setVariable('campaign', '夏限定 半額キャンペーン')
    ->setVariable('tone', 'fresh, summery');

if (!$generator->validate()) {
    print_r($generator->getMissingRequiredVariables());
    exit;
}

$prompt = $generator->generate();
$client->generateImage($prompt, __DIR__ . '/output/banner.png');
```

`PromptTemplateFactory` でテンプレートを登録 → キーで取り出すパターンも可能（複数バナータイプの切り替え等）。

```php
use YouCast\Gemini\NanoBanana\Factory\PromptTemplateFactory;

$factory = new PromptTemplateFactory();
$factory->register('sales-promotion', SalesPromotionTemplate::class);

$template = $factory->create('sales-promotion');
```

---

## ユースケース7: プロンプトファイルを使った画像編集

長文プロンプトを別ファイルで管理し、画像と組み合わせて編集を実行。

```php
// ./Prompt/photo_realism.txt
//   Make this scene look like a 1990s film photograph...

$client->editImageWithPromptFile(
    prompt_file_path: './Prompt/photo_realism.txt',
    image_path: __DIR__ . '/input.jpg',
    output_path: __DIR__ . '/output/film_style.png',
);
```

---

## レスポンス DTO

`NanoBananaResponseDto`:

| メソッド | 戻り値 |
|---|---|
| `getBase64()` | 画像のBase64文字列 |
| `getPromptTokenCount()` | プロンプト分のトークン |
| `getTotalTokenCount()` | 合計トークン |
| `getModelVersion()` | 実際に使われたモデルバージョン |
| `getResponseId()` | レスポンスID |
| `getRowResponse()` | API生レスポンス |
| `toArray()` | 全フィールド配列化 |

> ファイル保存は `generateImage()` 等が内部で完了しているため、DTO から直接 `saveTo()` する必要はありません。

## 例外

```php
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Exceptions\GeminiFileOperationException;
use YouCast\Gemini\NanoBanana\Exceptions\ImageProcessingException;
use YouCast\Gemini\Exceptions\GeminiException;

try {
    $client->generateImage('...', '/tmp/out.png');
} catch (ImageProcessingException $e) {
    // Base64デコード失敗、画像形式検証失敗 等
} catch (GeminiFileOperationException $e) {
    // 出力先ディレクトリ作成失敗、ファイル書き込み失敗 等
} catch (GeminiApiRequestException $e) {
    // HTTPエラー
} catch (GeminiApiKeyException $e) {
    // APIキー不正
} catch (GeminiException $e) {
    // 上記すべての基底
}
```

各例外は `getContext()` でリクエスト情報・ファイルパス・エラー詳細を返します。

## 画像形式検証を無効化

URLからの画像参照で MIME 判定が厳しすぎる場合は `is_image_validation: false` で緩和できます。

```php
$client = new NanoBananaClient(
    api_key: getenv('GEMINI_API_KEY'),
    model: ImageModel::GEMINI_3_PRO_IMAGE_PREVIEW,
    is_image_validation: false,
);
```
