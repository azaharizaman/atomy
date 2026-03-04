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

        // Apply visibility
        $permissions = $visibility === Visibility::Public ? 0644 : 0600;
        if (!chmod($fullPath, $permissions)) {
            throw new \RuntimeException(sprintf('Failed to set permissions on file: "%s"', $fullPath));
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
            if (!unlink($fullPath)) {
                $error = error_get_last();
                throw new \RuntimeException(sprintf(
                    'Failed to delete file: "%s". %s',
                    $fullPath,
                    $error['message'] ?? ''
                ));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTemporaryUrl(string $path, int $ttlSeconds): string
    {
        // Don't expose absolute filesystem paths
        // In a real app, this would be a signed URL to a controller route
        // For local dev, we return a virtual path with URL-encoded segments
        $encoded = implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
        return "/storage/temp/" . $encoded . "?expires=" . (time() + $ttlSeconds);
    }

    private function getFullPath(string $path): string
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Path cannot be empty');
        }

        // Normalize path: remove leading slashes, handle dots
        $path = ltrim($path, '/');
        
        // Basic path traversal prevention
        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException(sprintf('Path traversal detected: "%s"', $path));
        }

        return $this->basePath . DIRECTORY_SEPARATOR . $path;
    }
}
