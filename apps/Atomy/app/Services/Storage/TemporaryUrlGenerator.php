<?php

declare(strict_types=1);

namespace App\Services\Storage;

use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Nexus\Storage\Contracts\PublicUrlGeneratorInterface;
use Nexus\Storage\Exceptions\StorageException;
use Throwable;

/**
 * TemporaryUrlGenerator implements URL generation for Laravel's Filesystem.
 *
 * This adapter provides public and temporary signed URL generation,
 * with support for various storage drivers (S3, local, etc.).
 *
 * @package App\Services\Storage
 */
readonly class TemporaryUrlGenerator implements PublicUrlGeneratorInterface
{
    /**
     * Create a new TemporaryUrlGenerator instance.
     *
     * @param Filesystem $filesystem The Laravel filesystem instance
     * @param string $diskName The storage disk name (for URL generation)
     */
    public function __construct(
        private Filesystem $filesystem,
        private string $diskName = 'local',
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getPublicUrl(string $path): string
    {
        try {
            return $this->filesystem->url($path);
        } catch (Throwable $e) {
            throw new StorageException("Failed to generate public URL for path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getTemporaryUrl(string $path, int $expirationInSeconds, array $options = []): string
    {
        if (!$this->supportsTemporaryUrls()) {
            throw new StorageException("Temporary URLs are not supported by the current storage driver");
        }

        try {
            $expiration = now()->addSeconds($expirationInSeconds);
            return $this->filesystem->temporaryUrl($path, $expiration, $options);
        } catch (Throwable $e) {
            throw new StorageException("Failed to generate temporary URL for path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getTemporaryUrlUntil(string $path, DateTimeInterface $expiresAt, array $options = []): string
    {
        if (!$this->supportsTemporaryUrls()) {
            throw new StorageException("Temporary URLs are not supported by the current storage driver");
        }

        try {
            return $this->filesystem->temporaryUrl($path, $expiresAt, $options);
        } catch (Throwable $e) {
            throw new StorageException("Failed to generate temporary URL for path: {$path}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function supportsTemporaryUrls(): bool
    {
        // Check if the filesystem adapter supports temporary URLs
        // In Laravel, this is primarily S3 and some other cloud storage drivers
        try {
            $adapter = $this->filesystem->getAdapter();

            // S3 adapter supports temporary URLs
            if ($adapter instanceof AwsS3V3Adapter) {
                return true;
            }

            // For other adapters, we need to check if they have the temporaryUrl method
            // Laravel's local adapter supports signed URLs via Storage::temporaryUrl
            return method_exists($this->filesystem, 'temporaryUrl');
        } catch (Throwable) {
            return false;
        }
    }
}
