# Translate

Google Cloud Translation API（v2 REST）のクライアント。多言語間の翻訳、HTMLタグ保持翻訳、検出元言語の自動判定に対応。

`GoogleTranslateClient` は `https://translation.googleapis.com/language/translate/v2` を叩く軽量ラッパー。

## サポート言語 (`TranslateLanguage`)

| ケース | コード | 言語 |
|---|---|---|
| `JAPANESE` | `ja` | 日本語 |
| `ENGLISH` | `en` | 英語 |
| `SPANISH` | `es` | スペイン語 |

> 他の言語を追加したい場合は `src/Translate/Enums/TranslateLanguage.php` にケースを追記してください（Google Cloud Translation API はISO-639-1コードに準拠）。

## フォーマット (`TranslateFormat`)

| ケース | 値 | 説明 |
|---|---|---|
| `TEXT` | `text` | プレーンテキスト（デフォルト） |
| `HTML` | `html` | HTMLタグを保持して翻訳 |

## モデル (`TranslateModel`)

| ケース | 値 |
|---|---|
| `NMT` | `nmt`（Neural Machine Translation） |

---

## ユースケース1: 単一テキストの翻訳

英語→日本語のもっとも基本的な使い方。

```php
use YouCast\Gemini\Translate\GoogleTranslateClient;
use YouCast\Gemini\Translate\Enums\TranslateLanguage;

$client = new GoogleTranslateClient(getenv('GOOGLE_TRANSLATE_API_KEY'));

$response = $client->translate('Hello, world!', TranslateLanguage::JAPANESE);
echo $response->getTranslatedText();          // こんにちは、世界！
echo $response->getDetectedSourceLanguage();  // en
```

`$source` を省略すると Google 側で自動検出される。

---

## ユースケース2: ソース言語を明示して翻訳

ユーザー入力の言語が確定している場合は明示すると速度が安定する。

```php
use YouCast\Gemini\Translate\Enums\TranslateLanguage;

$response = $client->translate(
    'おはようございます',
    target: TranslateLanguage::ENGLISH,
    source: TranslateLanguage::JAPANESE,
);
echo $response->getTranslatedText();  // Good morning.
```

---

## ユースケース3: 複数テキストの一括翻訳（コスト最適化）

API呼び出し回数を減らすため、配列で渡してまとめて翻訳。

```php
$texts = ['Hello', 'Goodbye', 'Thank you'];
$response = $client->translate($texts, TranslateLanguage::JAPANESE);

foreach ($response->getTranslatedTexts() as $i => $translated) {
    echo "{$texts[$i]} => {$translated}\n";
}
// Hello => こんにちは
// Goodbye => さようなら
// Thank you => ありがとう
```

各翻訳結果の詳細は `getTranslations()` で配列構造として取得可能。

```php
foreach ($response->getTranslations() as $t) {
    var_dump($t);
    // ['translated_text' => '...', 'detected_source_language' => 'en', 'model' => null]
}
```

---

## ユースケース4: HTML を保持したまま翻訳

ブログ記事やメール本文など、HTMLタグの構造を残したい場合。

```php
use YouCast\Gemini\Translate\Enums\TranslateFormat;
use YouCast\Gemini\Translate\Enums\TranslateLanguage;

$html = '<p>Welcome to our <strong>website</strong>!</p>';
$response = $client->translate(
    $html,
    target: TranslateLanguage::JAPANESE,
    source: TranslateLanguage::ENGLISH,
    format: TranslateFormat::HTML,
);

echo $response->getTranslatedText();
// <p>私たちの<strong>ウェブサイト</strong>へようこそ！</p>
```

`TranslateFormat::TEXT` のままだとタグごとエスケープされて翻訳される点に注意。

---

## ユースケース5: モデル指定

NMT モデルを明示的に指定（将来別モデルが増えた際に切り替え可能）。

```php
use YouCast\Gemini\Translate\Enums\TranslateModel;
use YouCast\Gemini\Translate\Enums\TranslateLanguage;

$response = $client->translate(
    'How are you today?',
    target: TranslateLanguage::JAPANESE,
    source: TranslateLanguage::ENGLISH,
    model: TranslateModel::NMT,
);
```

---

## ユースケース6: 多言語UIのバルク翻訳（実用パターン）

複数言語の翻訳辞書を作るスクリプトの最小例。

```php
$source_texts = [
    'login'  => 'Sign in',
    'logout' => 'Sign out',
    'submit' => 'Submit',
];

$targets = [TranslateLanguage::JAPANESE, TranslateLanguage::SPANISH];
$dictionary = [];

foreach ($targets as $target) {
    $response = $client->translate(
        array_values($source_texts),
        target: $target,
        source: TranslateLanguage::ENGLISH,
    );
    $translated = $response->getTranslatedTexts();
    foreach (array_keys($source_texts) as $i => $key) {
        $dictionary[$target->value][$key] = $translated[$i];
    }
}

file_put_contents('lang.json', json_encode($dictionary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
```

---

## レスポンス DTO

`TranslateResponseDto` の主なメソッド:

| メソッド | 戻り値 |
|---|---|
| `getTranslatedText()` | 最初の翻訳結果 |
| `getTranslatedTexts()` | 全翻訳結果の配列 |
| `getDetectedSourceLanguage()` | 自動検出された元言語コード（`source` 未指定時のみ） |
| `getTranslations()` | 全翻訳結果の構造化配列 |
| `getRowResponse()` | API生レスポンス |
| `toArray()` | DTO全体を配列化 |

## 例外

```php
use YouCast\Gemini\Translate\Exceptions\TranslateApiKeyException;
use YouCast\Gemini\Translate\Exceptions\TranslateApiRequestException;
use YouCast\Gemini\Exceptions\GeminiException;

try {
    $response = $client->translate('Hi', TranslateLanguage::JAPANESE);
} catch (TranslateApiKeyException $e) {
    // APIキー不正
} catch (TranslateApiRequestException $e) {
    // HTTPエラー / レート制限 等
    error_log(json_encode($e->getContext()));
} catch (GeminiException $e) {
    // 上記すべての基底でもキャッチ可能
}
```
