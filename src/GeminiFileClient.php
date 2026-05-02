<?php

namespace YouCast\Gemini;

use YouCast\Gemini\Common\Curl;
use YouCast\Gemini\Dto\FileDto;
use YouCast\Gemini\Dto\FileResponseDto;
use YouCast\Gemini\Enums\AiModel;
use YouCast\Gemini\Exceptions\GeminiApiRequestException;
use YouCast\Gemini\Exceptions\GeminiFileOperationException;

/**
 * Google Gemini File API クライアント
 *
 * 画像やファイルをアップロードしてgenerateContentで使用するためのクライアント
 */
final class GeminiFileClient
{
    public function __construct(
        private string $api_key
    ) {
    }

    /**
     * ファイルをアップロードする（Resumable Upload Protocol）
     *
     * @param FileDto $file_dto
     * @return FileResponseDto
     * @throws GeminiFileOperationException
     * @throws GeminiApiRequestException
     */
    public function upload(FileDto $file_dto): FileResponseDto
    {
        $upload_url = $this->initiateResumableUpload($file_dto);
        return $this->uploadFileContent($upload_url, $file_dto);
    }

    /**
     * Resumable upload を開始してupload URLを取得
     */
    private function initiateResumableUpload(FileDto $file_dto): string
    {
        try {
            $headers = [
                'x-goog-api-key: ' . $this->api_key,
                'X-Goog-Upload-Protocol: resumable',
                'X-Goog-Upload-Command: start',
                'X-Goog-Upload-Header-Content-Length: ' . $file_dto->getFileSize(),
                'X-Goog-Upload-Header-Content-Type: ' . $file_dto->getMimeType(),
                'Content-Type: application/json',
            ];
            $data = [
                'file' => [
                    'display_name' => $file_dto->getDisplayName(),
                ],
            ];

            $response = Curl::post(AiModel::getFileUploadUrl(), $headers, $data);

            return $response['upload_url'];
        } catch (GeminiApiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeminiApiRequestException(
                'Resumable upload の初期化中にエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * ファイルの内容をアップロード
     */
    private function uploadFileContent(string $upload_url, FileDto $file_dto): FileResponseDto
    {
        $binary_data = $this->getBinaryData($file_dto);
        return $this->uploadBinaryContent($upload_url, $binary_data, $file_dto->getFileSize());
    }

    /**
     * FileDtoからバイナリデータを取得
     */
    private function getBinaryData(FileDto $file_dto): string
    {
        if ($file_dto->isBinaryData()) {
            return $file_dto->getData();
        }

        if ($file_dto->isLocalFile()) {
            $content = @file_get_contents($file_dto->getData());
            if ($content === false) {
                throw new GeminiFileOperationException(
                    'ファイルの読み込みに失敗しました: ' . $file_dto->getData(),
                    0,
                    null,
                    ['file' => $file_dto->getData()]
                );
            }
            return $content;
        }

        if ($file_dto->isUrl()) {
            throw new GeminiFileOperationException(
                'URLからのアップロードは未対応です。先にファイルをダウンロードしてください。',
                0,
                null,
                ['url' => $file_dto->getData()]
            );
        }

        throw new GeminiFileOperationException(
            '不明なソースタイプです: ' . $file_dto->getSourceType(),
            0,
            null,
            ['source_type' => $file_dto->getSourceType()]
        );
    }

    /**
     * バイナリコンテンツをアップロード
     */
    private function uploadBinaryContent(string $upload_url, string $binary_data, int $file_size): FileResponseDto
    {
        try {
            $headers = [
                'x-goog-api-key: ' . $this->api_key,
                'Content-Length: ' . $file_size,
                'X-Goog-Upload-Offset: 0',
                'X-Goog-Upload-Command: upload, finalize',
            ];

            $response = Curl::post($upload_url, $headers, $binary_data);

            return new FileResponseDto($response['file'] ?? $response);
        } catch (GeminiApiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeminiApiRequestException(
                'ファイルアップロード中にエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * ファイルの状態を取得
     *
     * @param string $file_name ファイル名（files/xxx形式）
     * @return FileResponseDto
     * @throws GeminiApiRequestException
     */
    public function getFile(string $file_name): FileResponseDto
    {
        try {
            $headers = [
                'x-goog-api-key: ' . $this->api_key,
            ];
            $response = Curl::get(
                AiModel::getFilesUrl() . '/' . basename($file_name),
                $headers
            );

            return new FileResponseDto($response);
        } catch (GeminiApiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeminiApiRequestException(
                'ファイル情報の取得中にエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                ['file_name' => $file_name]
            );
        }
    }

    /**
     * ファイルがアクティブになるまで待機
     *
     * @param string $file_name ファイル名（files/xxx形式）
     * @param int $max_attempts 最大試行回数
     * @param int $interval_seconds 試行間隔（秒）
     * @return FileResponseDto
     * @throws GeminiApiRequestException
     */
    public function waitForFileActive(string $file_name, int $max_attempts = 30, int $interval_seconds = 2): FileResponseDto
    {
        for ($i = 0; $i < $max_attempts; $i++) {
            $file = $this->getFile($file_name);

            if ($file->isActive()) {
                return $file;
            }

            if (!$file->isProcessing()) {
                throw new GeminiApiRequestException(
                    'ファイルの処理に失敗しました: ' . $file->getState(),
                    0,
                    null,
                    ['file_name' => $file_name, 'state' => $file->getState()]
                );
            }

            sleep($interval_seconds);
        }

        throw new GeminiApiRequestException(
            'ファイルの処理がタイムアウトしました',
            0,
            null,
            ['file_name' => $file_name, 'max_attempts' => $max_attempts]
        );
    }

    /**
     * ファイルを削除
     *
     * @param string $file_name ファイル名（files/xxx形式）
     * @return bool
     * @throws GeminiApiRequestException
     */
    public function deleteFile(string $file_name): bool
    {
        try {
            $headers = [
                'x-goog-api-key: ' . $this->api_key,
            ];
            Curl::delete(
                AiModel::getFilesUrl() . '/' . basename($file_name),
                $headers
            );

            return true;
        } catch (GeminiApiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeminiApiRequestException(
                'ファイルの削除中にエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                ['file_name' => $file_name]
            );
        }
    }

    /**
     * アップロードされたファイル一覧を取得
     *
     * @param int $page_size ページサイズ
     * @param string|null $page_token ページトークン
     * @return array{files: FileResponseDto[], next_page_token: string|null}
     * @throws GeminiApiRequestException
     */
    public function getFiles(int $page_size = 100, ?string $page_token = null): array
    {
        try {
            $query = ['pageSize' => $page_size];
            if ($page_token) {
                $query['pageToken'] = $page_token;
            }

            $headers = [
                'x-goog-api-key: ' . $this->api_key,
            ];
            $response = Curl::get(AiModel::getFilesUrl(), $headers, $query);

            $files = [];
            foreach ($response['files'] ?? [] as $file) {
                $files[] = new FileResponseDto($file);
            }

            return [
                'files' => $files,
                'next_page_token' => $response['nextPageToken'] ?? null,
            ];
        } catch (GeminiApiRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GeminiApiRequestException(
                'ファイル一覧の取得中にエラーが発生しました: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
