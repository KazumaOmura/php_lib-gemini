# Veo

Google Veo（動画生成モデル）のクライアント。テキストプロンプトから MP4 動画を生成し、Long Running Operation (LRO) のポーリングまで自動で行う。

## サポートモデル (`VideoModel`)

| ケース | モデル名 |
|---|---|
| `VEO_3_1_GENERATE_PREVIEW` | `veo-3.1-generate-preview` |

## 動作の流れ

```
1. POST predictLongRunning           → operation を取得
2. GET  /v1beta/operations/{name}    → done になるまでポーリング（既定: 10秒間隔 × 60回 = 最大10分）
3. response.predictions[0].bytesBase64Encoded をデコード
4. output_path に MP4 を保存
```

---

## ユースケース1: 最小サンプル

```php
use YouCast\Gemini\Veo\VeoClient;
use YouCast\Gemini\Veo\Enums\VideoModel;

$client = new VeoClient(getenv('GEMINI_API_KEY'), VideoModel::VEO_3_1_GENERATE_PREVIEW);

$dto = $client->generateVideo(
    prompt: '海辺の夕焼け、波打ち際を歩く犬、シネマティックなスローモーション',
    output_path: __DIR__ . '/output/sunset_dog.mp4',
);

echo "MIME: " . $dto->getMimeType() . PHP_EOL;        // video/mp4
echo "Operation: " . $dto->getOperationName() . PHP_EOL;
```

`generateVideo()` 内でポーリング → ファイル保存まで完了する。

---

## ユースケース2: ポーリング間隔の調整

長尺・高品質の動画ではデフォルト10分でタイムアウトすることがある。間隔と回数を調整。

```php
$client = new VeoClient(getenv('GEMINI_API_KEY'), VideoModel::VEO_3_1_GENERATE_PREVIEW);

// 15秒間隔 × 80回 = 最大20分まで待つ
$client->setPollingConfig(interval_seconds: 15, max_attempts: 80);

$dto = $client->generateVideo(
    prompt: 'A 30-second cinematic dolly shot through a futuristic Tokyo street at night',
    output_path: __DIR__ . '/output/tokyo_night.mp4',
);
```

逆に短時間で諦めたい場合は `setPollingConfig(5, 12)`（最大1分）など。

---

## ユースケース3: バックグラウンドジョブとして実行

CLI スクリプトや Laravel Queue から走らせる典型パターン。

```php
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Veo\Exceptions\VideoProcessingException;

try {
    $client = (new VeoClient(getenv('GEMINI_API_KEY'), VideoModel::VEO_3_1_GENERATE_PREVIEW))
        ->setPollingConfig(20, 60);  // 20秒 × 60回 = 最大20分

    $dto = $client->generateVideo(
        prompt: $job->getPrompt(),
        output_path: $job->getOutputPath(),
    );

    $job->markCompleted($dto->getOperationName());
} catch (GeminiApiRequestException $e) {
    // タイムアウト or HTTPエラー → 再試行 or 失敗扱い
    $job->markFailed($e->getMessage(), $e->getContext());
} catch (VideoProcessingException $e) {
    // Base64デコード失敗等
    $job->markFailed($e->getMessage(), $e->getContext());
}
```

---

## ユースケース4: APIキーのマスク表示（ログ用途）

ログに API キーをそのまま出さないためのヘルパー。

```php
$client = new VeoClient('abcd1234efghijkl5678', VideoModel::VEO_3_1_GENERATE_PREVIEW);
error_log('Veo client started: ' . $client->getApiKey());
// → "Veo client started: abcd1234...5678"
```

---

## ユースケース5: リクエスト内容の検査（デバッグ・監査）

実際にAPIへ送るペイロードを保存しておきたい場合。

```php
$client = new VeoClient(getenv('GEMINI_API_KEY'), VideoModel::VEO_3_1_GENERATE_PREVIEW);
$client->setPollingConfig(0, 1);  // ローカルテスト用

try {
    $client->generateVideo('test prompt', '/tmp/out.mp4');
} catch (\Throwable $e) {
    file_put_contents('debug_payload.json', json_encode($client->getRequestData(), JSON_PRETTY_PRINT));
    throw $e;
}
```

---

## レスポンス DTO

`VeoResponseDto`:

| メソッド | 戻り値 |
|---|---|
| `getBase64()` | 動画のBase64文字列 |
| `getMimeType()` | `video/mp4` 等 |
| `getOperationName()` | LRO の operations/{id} |
| `getRowResponse()` | API生レスポンス |
| `toArray()` | 全フィールド配列化 |

> Base64 はファイル保存後でも DTO に残っているため、別形式に変換したい場合（例: GCS にアップロード）も再利用できます。

```php
// 例: 同じバイナリを別の保存先にも回す
file_put_contents('/backup/' . basename($output_path), base64_decode($dto->getBase64()));
```

---

## 例外

```php
use YouCast\Gemini\Exceptions\GeminiApiKeyException;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Exceptions\GeminiFileOperationException;
use YouCast\Gemini\Veo\Exceptions\VideoProcessingException;
use YouCast\Gemini\Exceptions\GeminiException;

try {
    $client->generateVideo('...', '/tmp/out.mp4');
} catch (VideoProcessingException $e) {
    // Base64 が空 / デコード失敗
} catch (GeminiFileOperationException $e) {
    // 出力ディレクトリ作成失敗 / ファイル書き込み失敗
} catch (GeminiApiRequestException $e) {
    // HTTPエラー、ポーリングタイムアウト、operations 取得失敗
    error_log(json_encode($e->getContext()));
} catch (GeminiApiKeyException $e) {
    // 認証エラー
} catch (GeminiException $e) {
    // 上記すべての基底
}
```

## 想定リトライ戦略

LROの中で失敗すると `GeminiApiRequestException` がスローされる。`getContext()` に `operation_name` が含まれているため、別ジョブで再ポーリングすることも可能（同じoperation_nameをそのままGETすれば良い）。再実行が必要なら `generateVideo()` を再度呼ぶ。
