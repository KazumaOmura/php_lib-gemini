<?php

namespace YouCast\Gemini\Common;

use YouCast\Gemini\Exceptions\GeminiApiRequestException;

class Curl
{
    public static function get(string $url, array $headers, array $query = []): array
    {
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return self::exec($url, $headers, null, 'GET');
    }

    public static function post(string $url, array $headers, array|string $data): array
    {
        return self::exec($url, $headers, $data, 'POST');
    }

    public static function delete(string $url, array $headers): array
    {
        return self::exec($url, $headers, null, 'DELETE');
    }

    /**
     * HTTPリクエストを実行
     *
     * @param string $url リクエストURL
     * @param array $headers HTTPヘッダー
     * @param array|string|null $data リクエストデータ（配列=JSON、文字列=バイナリ、null=なし）
     * @param string $method HTTPメソッド
     * @return array レスポンス
     * @throws GeminiApiRequestException
     */
    private static function exec(string $url, array $headers, array|string|null $data, string $method): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
        }

        $raw_response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        curl_close($ch);

        if ($raw_response === false) {
            throw new GeminiApiRequestException(
                'APIリクエストが失敗しました（cURLエラー）: ' . $curl_error,
                0,
                null,
            );
        }

        $response_headers = substr($raw_response, 0, $header_size);
        $body = substr($raw_response, $header_size);

        if ($http_status < 200 || $http_status >= 300) {
            throw new GeminiApiRequestException(
                'APIリクエストが失敗しました: ' . $http_status . ' - ' . $body,
                $http_status,
                null,
            );
        }

        $result = [];

        // X-Goog-Upload-URLヘッダーを抽出
        if (preg_match('/x-goog-upload-url:\s*(.+)/i', $response_headers, $matches)) {
            $result['upload_url'] = trim($matches[1]);
        }

        // ボディがある場合はJSONとしてパース
        if (!empty(trim($body))) {
            $json = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new GeminiApiRequestException(
                    'APIレスポンスのJSONパースに失敗しました: ' . $body,
                    0,
                    null,
                );
            }
            $result = array_merge($result, $json);
        }

        return $result;
    }
}
