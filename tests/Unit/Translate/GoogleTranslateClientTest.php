<?php

namespace YouCast\Gemini\Tests\Unit\Translate;

use PHPUnit\Framework\TestCase;
use YouCast\Gemini\Tests\Unit\CurlMockState;
use YouCast\Gemini\Translate\Dto\TranslateResponseDto;
use YouCast\Gemini\Translate\Enums\TranslateFormat;
use YouCast\Gemini\Translate\Enums\TranslateLanguage;
use YouCast\Gemini\Translate\Enums\TranslateModel;
use YouCast\Gemini\Translate\Exceptions\TranslateApiRequestException;
use YouCast\Gemini\Translate\GoogleTranslateClient;

class GoogleTranslateClientTest extends TestCase
{
    protected function setUp(): void
    {
        CurlMockState::reset();
    }

    public function test_translate_returns_response_dto(): void
    {
        CurlMockState::setJsonResponse([
            'data' => [
                'translations' => [
                    [
                        'translatedText' => 'こんにちは',
                        'detectedSourceLanguage' => 'en',
                    ],
                ],
            ],
        ]);

        $client = new GoogleTranslateClient('test-api-key');
        $result = $client->translate('Hello', TranslateLanguage::JAPANESE);

        $this->assertInstanceOf(TranslateResponseDto::class, $result);
        $this->assertSame('こんにちは', $result->getTranslatedText());
        $this->assertSame('en', $result->getDetectedSourceLanguage());
    }

    public function test_translate_with_source_language(): void
    {
        CurlMockState::setJsonResponse([
            'data' => [
                'translations' => [
                    ['translatedText' => 'Hola'],
                ],
            ],
        ]);

        $client = new GoogleTranslateClient('test-api-key');
        $client->translate(
            'Hello',
            TranslateLanguage::SPANISH,
            TranslateLanguage::ENGLISH,
            TranslateFormat::TEXT,
            TranslateModel::NMT
        );

        $requestData = $client->getRequestData();
        $this->assertSame('en', $requestData['source']);
        $this->assertSame('es', $requestData['target']);
        $this->assertSame('text', $requestData['format']);
        $this->assertSame('nmt', $requestData['model']);
    }

    public function test_translate_multiple_texts(): void
    {
        CurlMockState::setJsonResponse([
            'data' => [
                'translations' => [
                    ['translatedText' => 'こんにちは'],
                    ['translatedText' => 'さようなら'],
                ],
            ],
        ]);

        $client = new GoogleTranslateClient('test-api-key');
        $result = $client->translate(['Hello', 'Goodbye'], TranslateLanguage::JAPANESE);

        $this->assertSame(['こんにちは', 'さようなら'], $result->getTranslatedTexts());
    }

    public function test_translate_wraps_exception(): void
    {
        CurlMockState::setFailure('Connection refused');

        $client = new GoogleTranslateClient('test-api-key');

        $this->expectException(TranslateApiRequestException::class);
        $client->translate('Hello', TranslateLanguage::JAPANESE);
    }
}
