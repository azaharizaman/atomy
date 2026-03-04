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
            mkdir($this->basePath, 0755, true);
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
            mkdir($directory, 0755, true);
        }

        $target = fopen($fullPath, 'wb');
        stream_copy_to_stream($stream, $target);
        fclose($target);
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

        return fopen($fullPath, 'rb');
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
