<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\StorageDriverInterface;
use Nexus\Document\ValueObjects\Visibility;

/**
 * Local filesystem storage driver.
 * ⚠️ WARNING: For development use only. Production should use S3.
 */
final class LocalStorageDriver implements StorageDriverInterface
{
    private string $basePath;

    public function __construct(string $basePath = '/tmp/atomy/storage')
    {
        $this->basePath = rtrim($basePath, '/');
        
        if (!is_dir($this->basePath)) {
            if (!mkdir($this->basePath, 0755, true) && !is_dir($this->basePath)) {
                throw new \RuntimeException(sprintf('Failed to create base directory "%s"', $this->basePath));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $path, $stream, Visibility $visibility): void
    {
        $fullPath = $this->getFullPath($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Failed to create directory "%s"', $directory));
            }
        }

        $target = fopen($fullPath, 'wb');
        if ($target === false) {
            throw new \RuntimeException(sprintf('Failed to open file for writing: "%s"', $fullPath));
        }

        try {
            if (stream_copy_to_stream($stream, $target) === false) {
                throw new \RuntimeException(sprintf('Failed to write stream to file: "%s"', $fullPath));
            }
        } finally {
            if (is_resource($target)) {
                fclose($target);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $path)
    {
        $fullPath = $this->getFullPath($path);
        
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        if (!is_readable($fullPath)) {
            throw new \RuntimeException(sprintf('File is not readable: "%s"', $fullPath));
        }

        $stream = fopen($fullPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException(sprintf('Failed to open file for reading: "%s"', $fullPath));
        }

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $path): void
    {
        $fullPath = $this->getFullPath($path);
        
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTemporaryUrl(string $path, int $ttlSeconds): string
    {
        // Local files don't have temporary signed URLs in this implementation
        // Just return a local file path or mock URL
        return "file://{$this->getFullPath($path)}?expires=" . (time() + $ttlSeconds);
    }

    private function getFullPath(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }
}
