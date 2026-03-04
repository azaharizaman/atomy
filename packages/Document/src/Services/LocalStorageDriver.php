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
    private string $urlSecret;

    public function __construct(
        string $basePath = '/tmp/atomy/storage',
        string $urlSecret = 'dev-secret'
    ) {
        $this->basePath = rtrim($basePath, '/');
        $this->urlSecret = $urlSecret;
        
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
        // 1. Normalize path (remove leading slashes, resolve . and ..)
        $normalizedPath = $this->normalizePath($path);

        // 2. Enforce safe maximum TTL (e.g., 7 days)
        $maxTtl = 7 * 24 * 3600;
        $actualTtl = min($ttlSeconds, $maxTtl);
        $expires = time() + $actualTtl;

        // 3. Compute HMAC signature over path and expiry
        $signature = hash_hmac('sha256', "{$normalizedPath}|{$expires}", $this->urlSecret);

        // 4. URL-encode segments and return signed virtual path
        $encoded = implode('/', array_map('rawurlencode', explode('/', $normalizedPath)));
        
        return sprintf(
            "/storage/temp/%s?expires=%d&signature=%s",
            $encoded,
            $expires,
            $signature
        );
    }

    private function getFullPath(string $path): string
    {
        $normalizedPath = $this->normalizePath($path);

        return $this->basePath . DIRECTORY_SEPARATOR . $normalizedPath;
    }

    private function normalizePath(string $path): string
    {
        if (empty($path)) {
            throw new \InvalidArgumentException('Path cannot be empty');
        }

        // Remove leading/trailing slashes
        $path = trim($path, '/');

        // Resolve dots
        $segments = explode('/', $path);
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '') {
                continue;
            }
            if ($segment === '..') {
                if (empty($resolved)) {
                    throw new \InvalidArgumentException(sprintf('Path traversal detected: "%s"', $path));
                }
                array_pop($resolved);
                continue;
            }
            $resolved[] = $segment;
        }

        return implode(DIRECTORY_SEPARATOR, $resolved);
    }
}
