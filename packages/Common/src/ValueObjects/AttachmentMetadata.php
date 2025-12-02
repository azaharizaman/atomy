<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable attachment metadata value object.
 * 
 * Represents file metadata for invoices, contracts, HR documents, etc.
 */
final readonly class AttachmentMetadata implements SerializableVO
{
    /**
     * @param string $fileName Original file name
     * @param string $mimeType MIME type (e.g., 'application/pdf')
     * @param int $sizeInBytes File size in bytes
     * @param \DateTimeImmutable $uploadedAt When file was uploaded
     * @param string $uploadedBy User ID who uploaded the file
     * @param string|null $storagePath Path/key in storage system
     * @throws InvalidValueException
     */
    public function __construct(
        private string $fileName,
        private string $mimeType,
        private int $sizeInBytes,
        private \DateTimeImmutable $uploadedAt,
        private string $uploadedBy,
        private ?string $storagePath = null
    ) {
        if (empty(trim($fileName))) {
            throw new InvalidValueException('File name is required');
        }

        if ($sizeInBytes < 0) {
            throw new InvalidValueException('File size cannot be negative');
        }

        if (empty(trim($mimeType))) {
            throw new InvalidValueException('MIME type is required');
        }
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSizeInBytes(): int
    {
        return $this->sizeInBytes;
    }

    public function getUploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function getUploadedBy(): string
    {
        return $this->uploadedBy;
    }

    public function getStoragePath(): ?string
    {
        return $this->storagePath;
    }

    public function getFileExtension(): string
    {
        $parts = explode('.', $this->fileName);
        return strtolower(end($parts));
    }

    public function getHumanReadableSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->sizeInBytes;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'file_name' => $this->fileName,
            'mime_type' => $this->mimeType,
            'size_in_bytes' => $this->sizeInBytes,
            'uploaded_at' => $this->uploadedAt->format('Y-m-d H:i:s'),
            'uploaded_by' => $this->uploadedBy,
            'storage_path' => $this->storagePath,
            'extension' => $this->getFileExtension(),
            'human_readable_size' => $this->getHumanReadableSize(),
        ];
    }

    public function toString(): string
    {
        return "{$this->fileName} ({$this->getHumanReadableSize()})";
    }

    public static function fromArray(array $data): static
    {
        return new self(
            fileName: $data['file_name'],
            mimeType: $data['mime_type'],
            sizeInBytes: $data['size_in_bytes'],
            uploadedAt: new \DateTimeImmutable($data['uploaded_at']),
            uploadedBy: $data['uploaded_by'],
            storagePath: $data['storage_path'] ?? null
        );
    }
}
