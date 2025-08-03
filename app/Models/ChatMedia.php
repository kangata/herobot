<?php

namespace App\Models;

class ChatMedia
{
    /**
     * @var string Base64 encoded data
     */
    public string $data;

    /**
     * @var string MIME type
     */
    public string $mime_type;

    /**
     * @var string|null File path if media is stored on disk
     */
    public ?string $file_path = null;

    public function __construct(string $data, string $mime_type, ?string $file_path = null)
    {
        $this->data = $data;
        $this->mime_type = $mime_type;
        $this->file_path = $file_path;
    }

    /**
     * Check if this media is a file path instead of base64 data
     */
    public function isFilePath(): bool
    {
        return !empty($this->file_path);
    }

    /**
     * Get the actual data - either from file or base64
     */
    public function getData(): string
    {
        if ($this->isFilePath() && file_exists($this->file_path)) {
            return base64_encode(file_get_contents($this->file_path));
        }

        return $this->data;
    }

    /**
     * Get file size in bytes
     */
    public function getSize(): int
    {
        if ($this->isFilePath() && file_exists($this->file_path)) {
            return filesize($this->file_path);
        }

        // Estimate size from base64 data
        return (int) (strlen($this->data) * 0.75);
    }
}
