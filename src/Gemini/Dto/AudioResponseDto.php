<?php

namespace YouCast\Gemini\Gemini\Dto;

/**
 * Gemini TTS (Text-to-Speech) レスポンス DTO
 *
 * Gemini API の generateContent で responseModalities=["AUDIO"] を指定したときに返る
 * PCM音声を扱う。デフォルトは 24kHz / 16-bit / モノラルの "audio/pcm"。
 *
 * そのままだとプレーンPCM（ヘッダなし）なので、ブラウザや一般プレイヤーで再生したい場合は
 * saveAsWav() を使うと WAV ヘッダを付けて保存できる。
 */
class AudioResponseDto
{
    private ?string $audio_base64 = null;
    private ?string $audio_mime_type = null;
    private int $prompt_token_count = 0;
    private int $candidates_token_count = 0;
    private int $total_token_count = 0;
    private string $model_version = '';
    private string $response_id = '';

    public function __construct(
        private array $row_response
    ) {
        $parts = $this->row_response['candidates'][0]['content']['parts'] ?? [];

        foreach ($parts as $part) {
            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
            if ($inline !== null) {
                $this->audio_base64 = $inline['data'] ?? null;
                $this->audio_mime_type = $inline['mimeType'] ?? $inline['mime_type'] ?? null;
                break;
            }
        }

        if (isset($this->row_response['usageMetadata']['promptTokenCount'])) {
            $this->prompt_token_count = (int) $this->row_response['usageMetadata']['promptTokenCount'];
        }
        if (isset($this->row_response['usageMetadata']['candidatesTokenCount'])) {
            $this->candidates_token_count = (int) $this->row_response['usageMetadata']['candidatesTokenCount'];
        }
        if (isset($this->row_response['usageMetadata']['totalTokenCount'])) {
            $this->total_token_count = (int) $this->row_response['usageMetadata']['totalTokenCount'];
        }

        $this->model_version = $this->row_response['modelVersion'] ?? '';
        $this->response_id = $this->row_response['responseId'] ?? '';
    }

    public function getAudioBase64(): ?string
    {
        return $this->audio_base64;
    }

    public function getAudioBytes(): ?string
    {
        if ($this->audio_base64 === null) {
            return null;
        }
        $decoded = base64_decode($this->audio_base64, true);
        return $decoded === false ? null : $decoded;
    }

    /**
     * MIMEタイプ。デフォルトは "audio/L16;codec=pcm;rate=24000" 等
     */
    public function getAudioMimeType(): ?string
    {
        return $this->audio_mime_type;
    }

    public function hasAudio(): bool
    {
        return $this->audio_base64 !== null;
    }

    /**
     * PCMバイト列をそのままファイル保存（ヘッダなし）
     */
    public function saveAudioTo(string $path): bool
    {
        $bytes = $this->getAudioBytes();
        if ($bytes === null) {
            return false;
        }
        return file_put_contents($path, $bytes) !== false;
    }

    /**
     * WAVヘッダを付けて .wav として保存
     *
     * Gemini TTS のPCMはデフォルトで 24kHz / 16-bit / モノラル。
     * MIMEタイプから sample rate が読み取れる場合はそれを使う。
     */
    public function saveAsWav(string $path): bool
    {
        $pcm = $this->getAudioBytes();
        if ($pcm === null) {
            return false;
        }

        $sample_rate = $this->detectSampleRate();
        $channels = 1;
        $bits_per_sample = 16;

        $byte_rate = $sample_rate * $channels * ($bits_per_sample / 8);
        $block_align = $channels * ($bits_per_sample / 8);
        $data_size = strlen($pcm);

        $header = 'RIFF'
            . pack('V', 36 + $data_size)
            . 'WAVE'
            . 'fmt '
            . pack('V', 16)
            . pack('v', 1)
            . pack('v', $channels)
            . pack('V', $sample_rate)
            . pack('V', $byte_rate)
            . pack('v', $block_align)
            . pack('v', $bits_per_sample)
            . 'data'
            . pack('V', $data_size);

        return file_put_contents($path, $header . $pcm) !== false;
    }

    /**
     * MIMEタイプから sample rate を抽出（例: "audio/L16;codec=pcm;rate=24000" → 24000）
     */
    private function detectSampleRate(int $default = 24000): int
    {
        if ($this->audio_mime_type !== null && preg_match('/rate=(\d+)/', $this->audio_mime_type, $m)) {
            return (int) $m[1];
        }
        return $default;
    }

    public function getPromptTokenCount(): int
    {
        return $this->prompt_token_count;
    }

    public function getCandidatesTokenCount(): int
    {
        return $this->candidates_token_count;
    }

    public function getTotalTokenCount(): int
    {
        return $this->total_token_count;
    }

    public function getModelVersion(): string
    {
        return $this->model_version;
    }

    public function getResponseId(): string
    {
        return $this->response_id;
    }

    public function getRowResponse(): array
    {
        return $this->row_response;
    }

    public function toArray(): array
    {
        return [
            'audio_base64' => $this->getAudioBase64(),
            'audio_mime_type' => $this->getAudioMimeType(),
            'has_audio' => $this->hasAudio(),
            'prompt_token_count' => $this->getPromptTokenCount(),
            'candidates_token_count' => $this->getCandidatesTokenCount(),
            'total_token_count' => $this->getTotalTokenCount(),
            'model_version' => $this->getModelVersion(),
            'response_id' => $this->getResponseId(),
            'row_response' => $this->getRowResponse(),
        ];
    }
}
