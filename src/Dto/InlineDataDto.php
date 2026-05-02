<?php

namespace YouCast\Gemini\Dto;

/**
 * Gemini APIのinline_data/ファイル用DTO
 *
 * Gemini APIでは
 *  - {"inline_data": {"mime_type": "...", "data": "..."}}
 *  - {"file_data": {"mime_type": "...", "file_uri": "..."}}
 * の両形式で画像やファイルを渡せるため、どちらにも対応できるDTO。
 */
class InlineDataDto
{
    private ?string $data = null;
    private ?string $file_uri = null;

    /**
     * コンストラクタ
     *
     * @param string $mime_type
     */
    public function __construct(
        private string $mime_type, // MIMEタイプ
    )
    {
    }

    /**
     * データを設定する
     * @param string $data Base64エンコードされたデータ
     */
    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * ファイルURIを設定する
     * @param string $file_uri ファイルストレージのURI
     */
    public function setFileUri(string $file_uri): self
    {
        $this->file_uri = $file_uri;

        return $this;
    }

    /**
     * inline_data 形式の配列を返す
     */
    public function toInlineDataPart(): array
    {
        if ($this->data === null) {
            throw new \LogicException('inline_dataとして Base64エンコードされたデータが必要です');
        }
        
        return [
            'inline_data' => [
                'mime_type' => $this->mime_type,
                'data' => $this->data,
            ]
        ];
    }

    /**
     * file_data 形式の配列を返す
     */
    public function toFileDataPart(): array
    {
        if ($this->file_uri === null) {
            throw new \LogicException('file_dataとして file_uri が必要です');
        }
        
        return [
            'file_data' => [
                'mime_type' => $this->mime_type,
                'file_uri' => $this->file_uri,
            ]
        ];
    }

    /**
     * データ型(どちらか)に合わせたAPI parts配列返却
     * 自動判定（file_uriがあればfile_data、dataがあればinline_data）
     */
    public function toGeminiPartArray(): array
    {
        if ($this->file_uri !== null) {
            return $this->toFileDataPart();
        }
        if ($this->data !== null) {
            return $this->toInlineDataPart();
        }
        throw new \LogicException('dataまたはfile_uriのいずれかが必要です');
    }

    /**
     * getterなど
     */
    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getFileUri(): ?string
    {
        return $this->file_uri;
    }
}
