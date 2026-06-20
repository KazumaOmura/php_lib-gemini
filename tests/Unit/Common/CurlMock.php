<?php

/**
 * curl_* 関数のオーバーライドと CurlMockState を共通化したテストブートストラップ。
 *
 * phpunit.xml の bootstrap で読み込まれ、全てのテストで共有される。
 * Curl ラッパー (YouCast\Gemini\Common\Curl) と同じ namespace に curl_* を再定義することで、
 * テスト時のみグローバル関数を差し替えられる。
 */

namespace YouCast\Gemini\Common;

function curl_init()
{
    return \YouCast\Gemini\Tests\Unit\CurlMockState::$curlHandle ?? 'mock_ch';
}

function curl_setopt($ch, $option, $value)
{
    \YouCast\Gemini\Tests\Unit\CurlMockState::$curlOptions[$option] = $value;
    return true;
}

function curl_exec($ch)
{
    return \YouCast\Gemini\Tests\Unit\CurlMockState::$curlExecResult;
}

function curl_error($ch)
{
    return \YouCast\Gemini\Tests\Unit\CurlMockState::$curlError;
}

function curl_getinfo($ch, $option = null)
{
    if ($option === CURLINFO_HTTP_CODE) {
        return \YouCast\Gemini\Tests\Unit\CurlMockState::$httpStatusCode;
    }
    if ($option === CURLINFO_HEADER_SIZE) {
        return \YouCast\Gemini\Tests\Unit\CurlMockState::$headerSize;
    }
    return null;
}

function curl_close($ch)
{
    return true;
}

namespace YouCast\Gemini\Tests\Unit;

class CurlMockState
{
    public static $curlHandle = 'mock_ch';
    public static string $curlExecResult = '';
    public static string $curlError = '';
    public static int $httpStatusCode = 200;
    public static int $headerSize = 0;
    public static array $curlOptions = [];

    public static function reset(): void
    {
        self::$curlHandle = 'mock_ch';
        self::$curlExecResult = '';
        self::$curlError = '';
        self::$httpStatusCode = 200;
        self::$headerSize = 0;
        self::$curlOptions = [];
    }

    public static function setJsonResponse(array $body, int $statusCode = 200, array $responseHeaders = []): void
    {
        $headerString = "HTTP/1.1 {$statusCode} OK\r\n";
        foreach ($responseHeaders as $name => $value) {
            $headerString .= "{$name}: {$value}\r\n";
        }
        $headerString .= "\r\n";

        $bodyString = json_encode($body);

        self::$httpStatusCode = $statusCode;
        self::$headerSize = strlen($headerString);
        self::$curlExecResult = $headerString . $bodyString;
        self::$curlError = '';
    }

    public static function setFailure(string $error = 'Connection refused'): void
    {
        self::$curlExecResult = false;
        self::$curlError = $error;
        self::$httpStatusCode = 0;
        self::$headerSize = 0;
    }
}
