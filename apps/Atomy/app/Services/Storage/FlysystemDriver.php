<?php

declare(strict_types=1);

namespace App\Services\Storage;

use DateTimeImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Nexus\Storage\Exceptions\FileNotFoundException;
use Nexus\Storage\Exceptions\InvalidPathException;
use Nexus\Storage\Exceptions\StorageException;
use Nexus\Storage\ValueObjects\FileMetadata;
use Nexus\Storage\ValueObjects\Visibility;
use Throwable;

/**
 * FlysystemDriver implements the StorageDriverInterface using Laravel's Filesystem.
 *
 * This adapter bridges the gap between the framework-agnostic Nexus\Storage contracts
 * and Laravel's Flysystem-based storage system.
 *
 * @package App\Services\Storage
 */
readonly class FlysystemDriver implements StorageDriverInterface
{
    /**
     * Create a new FlysystemDriver instance.
     *
     * @param Filesystem $filesystem The Laravel filesystem instance
     */
    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function put(string $path, mixed $contents, Visibility $visibility = Visibility::Private): bool
    {
        $this->validatePath($path);

        try {
            // Determine Laravel visibility
            $laravelVisibility = $visibility === Visibility::Public ? 'public' : 'private';

            // Handle stream resources
            if (is_resource($contents)) {
                return $this->filesystem->put($path, $contents, $laravelVisibility);
            }

            // Handle string contents
            return $this->filesystem->put($path, $contents, $laravelVisibility);
        } catch (Throwable $e) {
            throw new StorageException("Failed to store file at path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $path): mixed
    {
        $this->validatePath($path);

        if (!$this->exists($path)) {
            throw new FileNotFoundException($path);
        }

        try {
            return $this->filesystem->readStream($path);
        } catch (Throwable $e) {
            throw new StorageException("Failed to retrieve file at path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function exists(string $path): bool
    {
        try {
            return $this->filesystem->exists($path);
        } catch (Throwable $e) {
            throw new StorageException("Failed to check file existence at path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): bool
    {
        $this->validatePath($path);

        if (!$this->exists($path)) {
            throw new FileNotFoundException($path);
        }

        try {
            return $this->filesystem->delete($path);
        } catch (Throwable $e) {
            throw new StorageException("Failed to delete file at path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(string $path): FileMetadata
    {
        $this->validatePath($path);

        if (!$this->exists($path)) {
            throw new FileNotFoundException($path);
        }

        try {
            $size = $this->filesystem->size($path);
            $mimeType = $this->filesystem->mimeType($path);
            $lastModified = $this->filesystem->lastModified($path);
            $visibility = $this->filesystem->getVisibility($path);

            return new FileMetadata(
                path: $path,
                size: $size,
                mimeType: $mimeType,
                lastModified: new DateTimeImmutable('@' . $lastModified),
                visibility: $visibility === 'public' ? Visibility::Public : Visibility::Private,
                extra: [],
            );
        } catch (Throwable $e) {
            throw new StorageException("Failed to retrieve metadata for file at path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function setVisibility(string $path, Visibility $visibility): bool
    {
        $this->validatePath($path);

        if (!$this->exists($path)) {
            throw new FileNotFoundException($path);
        }

        try {
            $laravelVisibility = $visibility === Visibility::Public ? 'public' : 'private';
            return $this->filesystem->setVisibility($path, $laravelVisibility);
        } catch (Throwable $e) {
            throw new StorageException("Failed to set visibility for file at path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path): bool
    {
        $this->validatePath($path);

        try {
            return $this->filesystem->makeDirectory($path);
        } catch (Throwable $e) {
            throw new StorageException("Failed to create directory at path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function listFiles(string $path, bool $recursive = false): array
    {
        try {
            $files = $recursive
                ? $this->filesystem->allFiles($path)
                : $this->filesystem->files($path);

            $metadata = [];
            foreach ($files as $file) {
                try {
                    $metadata[] = $this->getMetadata($file);
                } catch (Throwable $e) {
                    // Skip files that can't be accessed
                    continue;
                }
            }

            return $metadata;
        } catch (Throwable $e) {
            throw new StorageException("Failed to list files at path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $destination): bool
    {
        $this->validatePath($source);
        $this->validatePath($destination);

        if (!$this->exists($source)) {
            throw new FileNotFoundException($source);
        }

        try {
            return $this->filesystem->copy($source, $destination);
        } catch (Throwable $e) {
            throw new StorageException("Failed to copy file from {$source} to {$destination}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function move(string $source, string $destination): bool
    {
        $this->validatePath($source);
        $this->validatePath($destination);

        if (!$this->exists($source)) {
            throw new FileNotFoundException($source);
        }

        try {
            return $this->filesystem->move($source, $destination);
        } catch (Throwable $e) {
            throw new StorageException("Failed to move file from {$source} to {$destination}", 0, $e);
        }
    }

    /**
     * Validate a file path to prevent security issues.
     *
     * @param string $path The path to validate
     *
     * @throws InvalidPathException If the path is invalid
     */
    private function validatePath(string $path): void
    {
        // Check for directory traversal attempts
        if (str_contains($path, '..')) {
            throw InvalidPathException::directoryTraversal($path);
        }

        // Check for absolute paths (we want relative paths only)
        if (str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:/', $path)) {
            throw InvalidPathException::absolutePathNotAllowed($path);
        }

        // Normalize path separators to forward slashes
        $normalizedPath = str_replace('\\', '/', $path);
        if ($normalizedPath !== $path) {
            throw new InvalidPathException($path, 'Use forward slashes (/) as path separators');
        }
    }
}
