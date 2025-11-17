<?php

declare(strict_types=1);

namespace Nexus\Storage\Contracts;

use DateTimeInterface;
use Nexus\Storage\Exceptions\StorageException;

/**
 * PublicUrlGeneratorInterface defines the contract for generating public URLs
 * to access files stored in the storage system.
 *
 * This interface supports both permanent public URLs and temporary signed URLs
 * with expiration times for secure, time-limited access to private files.
 *
 * @package Nexus\Storage\Contracts
 */
interface PublicUrlGeneratorInterface
{
    /**
     * Generate a permanent public URL for a file.
     *
     * This method should only be used for files with public visibility.
     *
     * @param string $path The storage path
     *
     * @return string The public URL
     *
     * @throws StorageException If URL generation fails
     */
    public function getPublicUrl(string $path): string;

    /**
     * Generate a temporary, signed URL for secure access to a file.
     *
     * The URL will expire after the specified duration and includes a
     * cryptographic signature to prevent tampering.
     *
     * @param string $path The storage path
     * @param int $expirationInSeconds The number of seconds until the URL expires
     * @param array<string, mixed> $options Additional options (e.g., response headers)
     *
     * @return string The temporary signed URL
     *
     * @throws StorageException If URL generation fails
     */
    public function getTemporaryUrl(string $path, int $expirationInSeconds, array $options = []): string;

    /**
     * Generate a temporary URL that expires at a specific date and time.
     *
     * @param string $path The storage path
     * @param DateTimeInterface $expiresAt The expiration date and time
     * @param array<string, mixed> $options Additional options
     *
     * @return string The temporary signed URL
     *
     * @throws StorageException If URL generation fails
     */
    public function getTemporaryUrlUntil(string $path, DateTimeInterface $expiresAt, array $options = []): string;

    /**
     * Check if the storage driver supports temporary URL generation.
     *
     * @return bool True if temporary URLs are supported, false otherwise
     */
    public function supportsTemporaryUrls(): bool;
}
