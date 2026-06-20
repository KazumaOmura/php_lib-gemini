# Gemini

Google Gemini API のクライアント群。テキスト生成、マルチモーダル入力、バッチ処理、File API、Vertex AI バッチ（GCS）に対応。

## 提供クライアント

| クラス | 用途 |
|---|---|
| `GeminiClient` | リアルタイムの `generateContent` 呼び出し。テキスト・画像・PDF を入力可能 |
| `GeminiBatchClient` | 48時間以内に処理される非同期バッチ（リアルタイムの50%割引） |
| `GeminiFileClient` | File API（Resumable Upload）。20MB超の画像・動画・PDF をアップロード |
| `VertexAiBatchClient` | Vertex AI BatchPredictionJobs（GCS連携、大規模ジョブ向け） |

## サポートモデル (`AiModel`)

| ケース | モデル名 |
|---|---|
| `GEMINI_3_1_PRO` | `gemini-3.1-pro-preview` |
| `GEMINI_3_PRO` | `gemini-3-pro-preview` |
| `GEMINI_3_FLASH` | `gemini-3-flash-preview` |
| `GEMINI_2_5_PRO` | `gemini-2.5-pro` |
| `GEMINI_2_5_FLASH` | `gemini-2.5-flash` |
| `GEMINI_2_0_FLASH` | `gemini-2.0-flash` |

---

## ユースケース1: シンプルなテキスト生成（JSONモード）

`request()` の第4引数 `$json_mode = true`（デフォルト）でJSONフォーマット強制。

```php
use YouCast\Gemini\Gemini\GeminiClient;
use YouCast\Gemini\Gemini\Enums\AiModel;

$client = new GeminiClient(getenv('GEMINI_API_KEY'), AiModel::GEMINI_2_5_FLASH);

$response = $client->request(
    '日本の四季を「春・夏・秋・冬」のキーで、それぞれ20字以内の説明を持つJSONで返してください。'
);

$data = json_decode($response->getContent(), true);
print_r($data);
// 出力例: ['春' => '桜が咲き温かい...', '夏' => '緑が深まる暑い...', ...]

echo "使用トークン: " . $response->getTotalTokenCount() . PHP_EOL;
```

`$json_mode = false` を指定するとフリーテキストで返ります。

---

## ユースケース2: 画像入力（マルチモーダル）

`InlineDataDto` で画像を埋め込み、画像説明や OCR を依頼。

```php
use YouCast\Gemini\Gemini\GeminiClient;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\FileMimeType;

$image = (new InlineDataDto(FileMimeType::IMAGE_PNG))
    ->setData(base64_encode(file_get_contents('receipt.png')));

$client = new GeminiClient(getenv('GEMINI_API_KEY'), AiModel::GEMINI_2_5_PRO);
$response = $client->request(
    'このレシートから店舗名、合計金額、購入日を抽出してJSONで返してください。',
    [$image]
);

print_r(json_decode($response->getContent(), true));
```

20MBを超える画像/PDF/動画を扱う場合は [File API](#ユースケース5-file-apiでのファイルアップロード) と `file_uri` を使用。

---

## ユースケース3: Safety Settings（有害コンテンツのフィルタ調整）

医療・法律など、デフォルトのフィルタが厳しすぎるドメインで `BLOCK_ONLY_HIGH` や `OFF` に下げる。

```php
use YouCast\Gemini\Gemini\GeminiClient;
use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\HarmCategory;
use YouCast\Gemini\Gemini\Enums\HarmBlockThreshold;

$client = (new GeminiClient(getenv('GEMINI_API_KEY'), AiModel::GEMINI_2_5_PRO))
    ->setSafetySettings([
        ['category' => HarmCategory::HATE_SPEECH,       'threshold' => HarmBlockThreshold::BLOCK_ONLY_HIGH],
        ['category' => HarmCategory::DANGEROUS_CONTENT, 'threshold' => HarmBlockThreshold::BLOCK_ONLY_HIGH],
    ]);

$response = $client->request('救急蘇生の基本手順を医療者向けに詳細に説明してください。');
```

| `HarmCategory` | `HarmBlockThreshold` |
|---|---|
| `HATE_SPEECH` | `BLOCK_NONE` |
| `SEXUALLY_EXPLICIT` | `BLOCK_ONLY_HIGH` |
| `HARASSMENT` | `BLOCK_MEDIUM_AND_ABOVE` |
| `DANGEROUS_CONTENT` | `BLOCK_LOW_AND_ABOVE` |
| `CIVIC_INTEGRITY` | `OFF` |

---

## ユースケース4: Thinking モード（深い推論を必要とするタスク）

`ThinkingLevel::HIGH` で内部推論トークンを最大化。`gemini-2.5-pro` 以降で利用可能。

```php
use YouCast\Gemini\Gemini\GeminiClient;
use YouCast\Gemini\Gemini\Dto\GenerationConfigDto;
use YouCast\Gemini\Gemini\Enums\AiModel;
use YouCast\Gemini\Gemini\Enums\ThinkingLevel;

$config = (new GenerationConfigDto())
    ->setThinkingLevel(ThinkingLevel::HIGH)
    ->setMaxOutputTokens(8192);

$client = (new GeminiClient(getenv('GEMINI_API_KEY'), AiModel::GEMINI_2_5_PRO))
    ->setGenerationConfig($config);

$response = $client->request('5x5 のN-クイーン問題の全解を列挙してください。');

echo "思考トークン: " . $response->getThoughtsTokenCount() . PHP_EOL;
echo $response->getContent();
```

---

## ユースケース5: File API でのファイルアップロード

大きな画像/動画/PDFは File API でアップロード → `file_uri` で参照。

```php
use YouCast\Gemini\Gemini\GeminiClient;
use YouCast\Gemini\Gemini\GeminiFileClient;
use YouCast\Gemini\Gemini\Dto\FileDto;
use YouCast\Gemini\Gemini\Dto\InlineDataDto;
use YouCast\Gemini\Gemini\Enums\AiModel;

// 1. アップロード
$file_client = new GeminiFileClient(getenv('GEMINI_API_KEY'));
$file_dto    = FileDto::fromLocalFile('/path/to/lecture.mp4', 'lecture.mp4');
$file        = $file_client->upload($file_dto);

// 2. ACTIVE になるまで待機（動画/大ファイルは数十秒～）
$file = $file_client->waitForFileActive($file->getName(), max_attempts: 60, interval_seconds: 5);

// 3. file_uri を使って Gemini に渡す
$ref = (new InlineDataDto($file->getMimeType()))->setFileUri($file->getUri());
$response = (new GeminiClient(getenv('GEMINI_API_KEY'), AiModel::GEMINI_2_5_PRO))
    ->request('この講義動画の要点を箇条書きでまとめて。', [$ref]);

echo $response->getContent();

// 4. 後始末
$file_client->deleteFile($file->getName());
```

`FileDto` のファクトリ:
- `FileDto::fromLocalFile($path, $display_name)`
- `FileDto::fromBinary($binary, $mime_type, $display_name)`
- `FileDto::fromUrl($url, $display_name, ?$mime_type)` — URLから自動ダウンロード

---

## ユースケース6: バッチ処理（コスト50%オフ）

大量の独立したリクエストを48時間以内に処理。

```php
use YouCast\Gemini\Gemini\GeminiBatchClient;
use YouCast\Gemini\Gemini\Enums\AiModel;

$client = new GeminiBatchClient(getenv('GEMINI_API_KEY'), AiModel::GEMINI_2_5_FLASH);

// 1. ジョブ投入
$batch = $client->create([
    ['prompt' => 'PHPで配列をソートする方法は?', 'key' => 'q1'],
    ['prompt' => 'PHP 8.3 の新機能は?',           'key' => 'q2'],
    ['prompt' => 'PSR-12 の要点は?',              'key' => 'q3'],
], display_name: 'php-faq-2026-06');

echo "投入: " . $batch->getName() . PHP_EOL;

// 2. 完了まで待機（最大30分、30秒間隔ポーリング）
$batch = $client->waitForCompletion($batch->getName(), max_attempts: 60, interval_seconds: 30);

// 3. 結果を key で引く
foreach (['q1', 'q2', 'q3'] as $key) {
    $item = $batch->findInlinedResponseByKey($key);
    echo "[$key] " . $item->getText() . PHP_EOL;
}
```

ジョブ管理:
- `$client->list()` — ジョブ一覧
- `$client->get($name)` — 個別取得
- `$client->cancel($name)` — キャンセル
- `$client->delete($name)` — 削除

---

## ユースケース7: Vertex AI バッチ（GCS 経由・大規模向け）

何万件規模のバッチを GCS 経由で投入する場合に使用。サービスアカウントJSONが必要。

```php
use YouCast\Gemini\Gemini\VertexAiBatchClient;
use YouCast\Gemini\Gemini\Enums\AiModel;

$client = new VertexAiBatchClient(
    service_account_json_path: '/etc/secrets/sa.json',
    project_id: 'my-gcp-project',
    location: 'us-central1',
    gcs_bucket: 'my-batch-bucket',
    model: AiModel::GEMINI_2_5_FLASH,
);

$batch = $client->create([
    ['prompt' => 'プロンプト1', 'key' => 'req-1'],
    ['prompt' => 'プロンプト2', 'key' => 'req-2'],
    // ... 数千〜数万件まで
], display_name: 'large-batch-2026-06');

// ステータス確認・キャンセル等は GeminiBatchClient と同じインターフェース
$batch = $client->get($batch->getName());
echo $batch->getState()->value;
```

---

## 例外ハンドリング

すべて `GeminiException` 基底でキャッチ可能。`getContext()` でリクエスト情報が取れます。

```php
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Exceptions\GeminiException;

try {
    $response = $client->request('...');
} catch (GeminiApiRequestException $e) {
    // HTTP エラー / タイムアウト / JSON パースエラー等
    error_log($e->getMessage());
    error_log(json_encode($e->getContext()));
} catch (GeminiException $e) {
    // それ以外の Gemini 系例外
}
```
