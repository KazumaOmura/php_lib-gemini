<?php

namespace YouCast\Gemini\Common;

use YouCast\Gemini\Exceptions\GeminiApiKeyException;

/**
 * Vertex AI OAuth2 認証ヘルパー
 *
 * サービスアカウントのJSONキーファイルからOAuth2アクセストークンを取得する
 */
class VertexAiAuth
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/cloud-platform';
    private const TOKEN_LIFETIME = 3600; // 1時間

    private ?string $cached_token = null;
    private ?int $token_expires_at = null;

    private array $service_account;

    public function __construct(string $service_account_json_path)
    {
        if (!file_exists($service_account_json_path)) {
            throw new GeminiApiKeyException(
                'サービスアカウントJSONファイルが見つかりません: ' . $service_account_json_path
            );
        }

        $json = file_get_contents($service_account_json_path);
        $this->service_account = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($this->service_account['private_key'])) {
            throw new GeminiApiKeyException(
                'サービスアカウントJSONファイルの形式が不正です'
            );
        }
    }

    /**
     * アクセストークンを取得（キャッシュ有効期限内はキャッシュを返す）
     */
    public function getAccessToken(): string
    {
        if ($this->cached_token !== null && $this->token_expires_at > time() + 60) {
            return $this->cached_token;
        }

        return $this->refreshToken();
    }

    /**
     * プロジェクトIDを取得
     */
    public function getProjectId(): string
    {
        return $this->service_account['project_id'] ?? '';
    }

    /**
     * JWTを生成してアクセストークンに交換
     */
    private function refreshToken(): string
    {
        $now = time();
        $jwt = $this->createJwt($now);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::TOKEN_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $http_status !== 200) {
            throw new GeminiApiKeyException(
                'OAuth2トークンの取得に失敗しました: ' . ($response ?: 'cURLエラー')
            );
        }

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            throw new GeminiApiKeyException(
                'OAuth2トークンレスポンスにaccess_tokenがありません: ' . $response
            );
        }

        $this->cached_token = $data['access_token'];
        $this->token_expires_at = $now + ($data['expires_in'] ?? self::TOKEN_LIFETIME);

        return $this->cached_token;
    }

    /**
     * JWT (JSON Web Token) を生成
     */
    private function createJwt(int $now): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $this->service_account['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + self::TOKEN_LIFETIME,
        ]));

        $signing_input = $header . '.' . $payload;

        $private_key = openssl_pkey_get_private($this->service_account['private_key']);
        if ($private_key === false) {
            throw new GeminiApiKeyException('秘密鍵の読み込みに失敗しました');
        }

        $signature = '';
        if (!openssl_sign($signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256)) {
            throw new GeminiApiKeyException('JWTの署名に失敗しました');
        }

        return $signing_input . '.' . $this->base64UrlEncode($signature);
    }

    /**
     * Base64 URL-safe エンコード
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
