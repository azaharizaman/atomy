<?php

declare(strict_types=1);

namespace Nexus\Storage\ValueObjects;

use DateTimeImmutable;

/**
 * FileMetadata represents metadata information about a stored file.
 *
 * This is an immutable value object that encapsulates file information
 * without exposing the underlying storage implementation details.
 *
 * @package Nexus\Storage\ValueObjects
 */
readonly class FileMetadata
{
    /**
     * Create a new FileMetadata instance.
     *
     * @param string $path The storage path of the file
     * @param int $size The file size in bytes
     * @param string $mimeType The MIME type of the file
     * @param DateTimeImmutable $lastModified The last modification timestamp
     * @param Visibility $visibility The file visibility
     * @param array<string, mixed> $extra Additional metadata (e.g., ETag, checksum)
     */
    public function __construct(
        public string $path,
        public int $size,
        public string $mimeType,
        public DateTimeImmutable $lastModified,
        public Visibility $visibility = Visibility::Private,
        public array $extra = [],
    ) {
    }

    /**
     * Get the file extension from the path.
     *
     * @return string|null The file extension or null if none exists
     */
    public function getExtension(): ?string
    {
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);
        return $extension !== '' ? $extension : null;
    }

    /**
     * Get the filename without the directory path.
     *
     * @return string The filename
     */
    public function getFilename(): string
    {
        return basename($this->path);
    }

    /**
     * Get the directory path without the filename.
     *
     * @return string The directory path
     */
    public function getDirectory(): string
    {
        return dirname($this->path);
    }

    /**
     * Check if the file is public.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->visibility->isPublic();
    }

    /**
     * Check if the file is private.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->visibility->isPrivate();
    }

    /**
     * Get the human-readable file size.
     *
     * @return string The formatted file size (e.g., "1.5 MB")
     */
    public function getFormattedSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get extra metadata value by key.
     *
     * @param string $key The metadata key
     * @param mixed $default The default value if key doesn't exist
     *
     * @return mixed The metadata value or default
     */
    public function getExtra(string $key, mixed $default = null): mixed
    {
        return $this->extra[$key] ?? $default;
    }

    /**
     * Create a new instance with updated visibility.
     *
     * @param Visibility $visibility The new visibility
     *
     * @return self A new FileMetadata instance
     */
    public function withVisibility(Visibility $visibility): self
    {
        return new self(
            path: $this->path,
            size: $this->size,
            mimeType: $this->mimeType,
            lastModified: $this->lastModified,
            visibility: $visibility,
            extra: $this->extra,
        );
    }
}
