# Changelog

このプロジェクトの主な変更点を記録します。
フォーマットは [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に準拠し、
バージョニングは [Semantic Versioning](https://semver.org/lang/ja/) に従います。

## [1.2.0] - 2026-08-19

### Added
- **Gemini テキスト生成 (`AiModel`)** に最新の Gemini 3.x Flash 系モデルを追加。
  - `GEMINI_3_7_FLASH` (`gemini-3.7-flash`) — 最新モデル（2026-08-13 GA）
  - `GEMINI_3_6_FLASH` (`gemini-3.6-flash`) — 2026-07-21 GA
  - `GEMINI_3_5_FLASH` (`gemini-3.5-flash`) — 2026-05-19 GA
  - `GEMINI_3_5_FLASH_LITE` (`gemini-3.5-flash-lite`) — 低コスト・低レイテンシ
  - `GEMINI_3_1_FLASH_LITE` (`gemini-3.1-flash-lite`) — 低コスト・低レイテンシ
- **画像生成 (`ImageModel`)** に GA 版 Nano Banana モデルを追加。
  - `GEMINI_3_PRO_IMAGE` (`gemini-3-pro-image`) — Nano Banana Pro（GA）
  - `GEMINI_3_1_FLASH_IMAGE` (`gemini-3.1-flash-image`) — Nano Banana 2（GA）
  - `GEMINI_3_1_FLASH_LITE_IMAGE` (`gemini-3.1-flash-lite-image`) — Nano Banana 2 Lite（GA）
- 新モデル向けのユニットテストを追加。

### Deprecated
- `AiModel::GEMINI_2_0_FLASH` (`gemini-2.0-flash`) — 2026-06-01 に提供終了（Shut down）。後方互換のため case は残置。
- `AiModel::GEMINI_3_PRO` (`gemini-3-pro-preview`) — 2026年に提供終了（Shut down）。後方互換のため case は残置。
- `ImageModel::GEMINI_3_PRO_IMAGE_PREVIEW` — GA 版 `GEMINI_3_PRO_IMAGE` へ移行推奨。
- `ImageModel::GEMINI_3_1_FLASH_IMAGE_PREVIEW` — GA 版 `GEMINI_3_1_FLASH_IMAGE` へ移行推奨。

### Changed
- README（ルート / Gemini / NanoBanana）のモデル一覧・サンプルコードを新モデルに合わせて更新。

## [1.1.1]

### Fixed
- TTS API のスピード選択肢を 5 → 9 に変更。

## [1.1.0]

### Added
- TTS API にスピードパラメータを追加。
- 音声生成・テキスト生成・JSON生成のメソッドを追加。
- Gemini の各機能を統合。

## [1.0.0]

- 初回リリース。

[1.2.0]: https://github.com/KazumaOmura/php_lib-gemini/compare/v1.1.1...v1.2.0
[1.1.1]: https://github.com/KazumaOmura/php_lib-gemini/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/KazumaOmura/php_lib-gemini/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/KazumaOmura/php_lib-gemini/releases/tag/v1.0.0
