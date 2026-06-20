# php_lib-gemini

Google AI の各種サービスを PHP から扱う統合クライアントライブラリ。

| プロバイダ | 機能 | 主なモデル | ドキュメント |
|---|---|---|---|
| **Gemini** | テキスト生成 / マルチモーダル / バッチ / File API | `gemini-3-pro-preview`, `gemini-3.1-pro-preview`, `gemini-2.5-pro` 他 | [src/Gemini/README.md](src/Gemini/README.md) |
| **Lyria** | 音楽生成（最長3分の楽曲・歌詞付き） | `lyria-3-pro-preview`, `lyria-3-clip-preview` | [src/Lyria/README.md](src/Lyria/README.md) |
| **Translate** | Google Cloud Translation API | NMT | [src/Translate/README.md](src/Translate/README.md) |
| **NanoBanana** | 画像生成・画像編集（Gemini Image系） | `gemini-3-pro-image-preview` (Nano Banana Pro)他 | [src/NanoBanana/README.md](src/NanoBanana/README.md) |
| **Veo** | 動画生成（LROポーリング） | `veo-3.1-generate-preview` | [src/Veo/README.md](src/Veo/README.md) |

## インストール

`composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/KazumaOmura/php_lib-gemini" }
    ],
    "require": {
        "youcast/php-lib-gemini": "^2.0"
    }
}
```

```bash
composer require youcast/php-lib-gemini
```

## 要件

- PHP 8.2 以上
- `ext-curl`, `ext-openssl` (Vertex AI のJWT署名で利用)
- Gemini / Lyria / Translate / NanoBanana / Veo を使う場合は Google AI Studio または Vertex AI のAPIキー / サービスアカウント

## ディレクトリ構成

```
src/
├── Common/           # Curl, VertexAiAuth (全プロバイダ共有)
├── Exceptions/       # GeminiException 系の基底例外
├── Enums/AiProvider  # 全プロバイダ列挙
├── Gemini/           # Gemini テキスト/マルチモーダル
├── Lyria/            # Lyria 音楽生成
├── Translate/        # Google Cloud Translation
├── NanoBanana/       # Nano Banana / Pro 画像生成
└── Veo/              # Veo 動画生成
```

各サブディレクトリの `README.md` にユースケース別のサンプルコードがあります。

## 最小サンプル

### テキスト生成（Gemini）

```php
use YouCast\Gemini\Gemini\GeminiClient;
use YouCast\Gemini\Gemini\Enums\AiModel;

$client = new GeminiClient(getenv('GEMINI_API_KEY'), AiModel::GEMINI_2_5_FLASH);
$response = $client->request('日本の代表的な観光地を3つ挙げて、JSONで返してください。');
echo $response->getContent();
```

### 画像生成（Nano Banana Pro）

```php
use YouCast\Gemini\NanoBanana\NanoBananaClient;
use YouCast\Gemini\NanoBanana\Enums\ImageModel;

$client = new NanoBananaClient(getenv('GEMINI_API_KEY'), ImageModel::GEMINI_3_PRO_IMAGE_PREVIEW);
$client->generateImage('夕焼けの海辺、油絵風', __DIR__ . '/sunset.png');
```

### 翻訳

```php
use YouCast\Gemini\Translate\GoogleTranslateClient;
use YouCast\Gemini\Translate\Enums\TranslateLanguage;

$client = new GoogleTranslateClient(getenv('GOOGLE_TRANSLATE_API_KEY'));
echo $client->translate('Hello, world!', TranslateLanguage::JAPANESE)->getTranslatedText();
```

### 音楽生成（Lyria 3 Pro）

```php
use YouCast\Gemini\Lyria\LyriaClient;
use YouCast\Gemini\Lyria\Enums\LyriaModel;

$client = LyriaClient::forGenerativeLanguage(getenv('GEMINI_API_KEY'), LyriaModel::LYRIA_3_PRO);
$response = $client->request('上品で都会的なシンセウェイブ。3分間のフルトラック。');
$response->saveAudioTo(__DIR__ . '/song.wav');
```

### 動画生成（Veo）

```php
use YouCast\Gemini\Veo\VeoClient;
use YouCast\Gemini\Veo\Enums\VideoModel;

$client = new VeoClient(getenv('GEMINI_API_KEY'), VideoModel::VEO_3_1_GENERATE_PREVIEW);
$client->generateVideo('海辺の夕焼けをスローモーションで', __DIR__ . '/sunset.mp4');
```

## 共通の例外設計

全プロバイダの例外は `YouCast\Gemini\Exceptions\GeminiException` を基底に持ち、`getContext(): array` で診断情報を保持します。

```
GeminiException                              （基底, context付き）
├── GeminiApiKeyException
├── GeminiApiRequestException
├── GeminiFileOperationException
└── GeminiInlineDataFileMimeTypeException

Translate/Exceptions/TranslateException      （GeminiException継承）
NanoBanana/Exceptions/ImageProcessingException
Veo/Exceptions/VideoProcessingException
```

`try/catch` は基本的に `GeminiException` で捕まえれば全プロバイダのエラーが拾えます。

```php
use YouCast\Gemini\Exceptions\GeminiException;

try {
    // ... 任意のプロバイダ呼び出し
} catch (GeminiException $e) {
    error_log($e->getMessage());
    error_log(json_encode($e->getContext(), JSON_UNESCAPED_UNICODE));
}
```

## テスト

```bash
composer install
vendor/bin/phpunit
```

## ライセンス

MIT License
