<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

/**
 * Domain interface for cloud storage providers.
 *
 * Vendors: Amazon S3, Google Cloud Storage, Azure Blob Storage, DigitalOcean Spaces, etc.
 */
interface CloudStorageConnectorInterface
{
    /**
     * Upload a file to cloud storage.
     *
     * @param string $path Destination path in storage
     * @param resource|string $contents File contents or stream
     * @param array<string, mixed> $options Additional options (visibility, mime_type, etc.)
     * @return array{path: string, url: string, size: int}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function upload(string $path, $contents, array $options = []): array;

    /**
     * Download a file from cloud storage.
     *
     * @param string $path File path in storage
     * @return resource File stream
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     * @throws \Nexus\Connector\Exceptions\FileNotFoundException
     */
    public function download(string $path);

    /**
     * Delete a file from cloud storage.
     *
     * @param string $path File path to delete
     * @return bool True if file was deleted
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function delete(string $path): bool;

    /**
     * Generate a temporary signed URL for secure file access.
     *
     * @param string $path File path in storage
     * @param int $expiresInSeconds URL expiration time in seconds
     * @return string Signed URL
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function generateSignedUrl(string $path, int $expiresInSeconds = 3600): string;
}
