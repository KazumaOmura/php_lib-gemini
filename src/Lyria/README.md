# Lyria

Google Lyria 3（音楽生成モデル）のクライアント。テキスト・画像プロンプトから歌詞付きの完成楽曲を生成する。

## サポートモデル (`LyriaModel`)

| ケース | モデル名 | 最大尺 | 用途 |
|---|---|---|---|
| `LYRIA_3_PRO` | `lyria-3-pro-preview` | 約 3 分（184秒） | フルトラック、verse/chorus/bridge 構造 |
| `LYRIA_3_CLIP` | `lyria-3-clip-preview` | 30 秒 | クリップ、SNS用ジングル |

## 2 種類のエンドポイント

`LyriaClient` は 2 通りのファクトリで生成する。どちらも同じインターフェースで `request()` を呼ぶ。

| ファクトリ | エンドポイント | 認証 | 想定用途 |
|---|---|---|---|
| `LyriaClient::forGenerativeLanguage($api_key, $model)` | `generativelanguage.googleapis.com` | `x-goog-api-key` | AI Studio で取得した個人/開発用APIキー |
| `LyriaClient::forVertexAi($access_token, $project_id, $model, $location = 'global')` | `*-aiplatform.googleapis.com` | `Authorization: Bearer` | GCPプロジェクトでの本番運用、サービスアカウント経由 |

---

## ユースケース1: テキスト→フルトラック生成（最も基本）

3分間の完成楽曲を生成して WAV で保存。

```php
use YouCast\Gemini\Lyria\LyriaClient;
use YouCast\Gemini\Lyria\Enums\LyriaModel;

$client = LyriaClient::forGenerativeLanguage(
    getenv('GEMINI_API_KEY'),
    LyriaModel::LYRIA_3_PRO
);

$response = $client->request(<<<PROMPT
Sophisticated, rhythmic, and aspirational track with crisp 808 percussion,
digital plucks, and muted electric guitar rhythmic strums.
Include breathy, airy Alto female vocal textures with melodic, minimalist
oohs and aahs with heavy reverb and rhythmic delay.
PROMPT);

// 歌詞や楽曲構造の説明（テキストパート）
echo $response->getText() . PHP_EOL;

// 音声データを保存
$response->saveAudioTo(__DIR__ . '/track.wav');

echo "MIME: " . $response->getAudioMimeType() . PHP_EOL;  // audio/wav 等
echo "使用トークン: " . $response->getTotalTokenCount() . PHP_EOL;
```

---

## ユースケース2: 短尺クリップ（30秒）の生成

`LYRIA_3_CLIP` でジングル・SNS向けの短尺音源を生成。

```php
use YouCast\Gemini\Lyria\LyriaClient;
use YouCast\Gemini\Lyria\Enums\LyriaModel;

$client = LyriaClient::forGenerativeLanguage(
    getenv('GEMINI_API_KEY'),
    LyriaModel::LYRIA_3_CLIP
);

$response = $client->request('Upbeat lofi hip-hop loop with mellow keys and tape hiss');
$response->saveAudioTo(__DIR__ . '/jingle.wav');
```

---

## ユースケース3: 画像をプロンプトに含めて雰囲気を伝える

画像から楽曲のムードを推定させる。

```php
use YouCast\Gemini\Lyria\LyriaClient;
use YouCast\Gemini\Lyria\Enums\LyriaModel;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Gemini\Enums\FileMimeType;

$image = (new InlineDataDto(FileMimeType::IMAGE_PNG))
    ->setData(base64_encode(file_get_contents('cherry_blossom.png')));

$client = LyriaClient::forGenerativeLanguage(getenv('GEMINI_API_KEY'), LyriaModel::LYRIA_3_CLIP);

$response = $client->request(
    'Generate an instrumental clip that starts slowly and builds in intensity, '
    . 'matching the mood of this image.',
    [$image]
);

$response->saveAudioTo(__DIR__ . '/clip.wav');
```

最大10枚まで画像を渡せます。

---

## ユースケース4: 歌詞を指定してボーカル付き楽曲を生成

ジャンル指定と歌詞をひとつのプロンプトに含める。

```php
$prompt = <<<PROMPT
Genre: Upbeat, acoustic Folk-Pop with a warm and cuddly vibe.
Bright acoustic guitars, a soft shaker rhythm, and a friendly, melodic vocal.

Lyrics:
Tail wags and a heavy head,
Time to curl up in your favorite bed.
Soft as a cloud, a dream come true,
The perfect spot for a dog like you.
PROMPT;

$response = $client->request($prompt);
$response->saveAudioTo(__DIR__ . '/dog_song.wav');
```

対応言語: English, German, Spanish, French, Hindi, Japanese, Korean, Portuguese。

---

## ユースケース5: Vertex AI 経由（本番運用）

GCPプロジェクトのサービスアカウントでアクセストークンを取得し、`forVertexAi()` を呼ぶ。

```php
use YouCast\Gemini\Common\VertexAiAuth;
use YouCast\Gemini\Lyria\LyriaClient;
use YouCast\Gemini\Lyria\Enums\LyriaModel;

$auth = new VertexAiAuth('/etc/secrets/sa.json');

$client = LyriaClient::forVertexAi(
    access_token: $auth->getAccessToken(),
    project_id:   $auth->getProjectId(),
    model:        LyriaModel::LYRIA_3_PRO,
    location:     'us-central1',          // 'global' も可
);

$response = $client->request('A cinematic orchestral piece with rising tension');
$response->saveAudioTo(__DIR__ . '/score.wav');
```

`VertexAiAuth::getAccessToken()` は 1時間キャッシュされるため、ループ内で何度呼んでも OAuth トークンエンドポイントには毎回叩きません。

---

## ユースケース6: テキストのみのレスポンス（楽曲構造プレビュー）

`$response_modalities` で `TEXT` のみを指定すると、楽曲生成せずに楽曲構造の説明だけを得られる（高速・低コスト）。

```php
$response = $client->request(
    'A peaceful jazz ballad in the style of 1960s New York clubs.',
    inline_data: [],
    response_modalities: ['TEXT'],
);

echo $response->getText();        // 楽曲構造のテキスト
var_dump($response->hasAudio()); // false
```

---

## レスポンス DTO の使い方

`LyriaResponseDto` の主なメソッド:

| メソッド | 戻り値 |
|---|---|
| `getText()` | 歌詞・楽曲構造説明（あれば） |
| `getAudioBase64()` | Base64 文字列のまま取得 |
| `getAudioBytes()` | デコード済みバイナリ |
| `getAudioMimeType()` | `audio/wav` / `audio/mpeg` 等 |
| `hasAudio()` | 音声が含まれているか |
| `saveAudioTo($path)` | ファイル保存（成功時 true） |
| `getTotalTokenCount()` | 使用トークン |

## 例外

`GeminiApiKeyException` / `GeminiApiRequestException` を `GeminiException` 基底で捕捉できる。

```php
use YouCast\Gemini\Exceptions\GeminiException;

try {
    $response = $client->request('...');
} catch (GeminiException $e) {
    error_log($e->getMessage());
    error_log(json_encode($e->getContext()));
}
```
