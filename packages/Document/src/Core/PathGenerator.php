<?php

declare(strict_types=1);

namespace Nexus\Document\Core;

/**
 * S3-optimized storage path generator.
 *
 * Generates nested directory paths for document storage with year/month
 * partitioning to optimize performance in object storage systems (S3, etc.).
 *
 * Path format: {tenantId}/{year}/{month}/{uuid}/v{version}.{extension}
 *
 * Benefits:
 * - Avoids hot partitions in S3 (distributes writes across prefixes)
 * - Enables efficient lifecycle policies (archive/delete by year/month)
 * - Improves list/search performance in object storage
 * - Simplifies cost optimization (move old prefixes to Glacier)
 */
final readonly class PathGenerator
{
    /**
     * Generate a storage path for a document or version.
     *
     * @param string $tenantId Tenant ULID
     * @param string $uuid Document ULID
     * @param int $version Version number (1-based)
     * @param string $extension File extension (without dot)
     * @param \DateTimeInterface|null $date Date for year/month (defaults to now)
     * @return string Storage path
     *
     * @example
     * generateStoragePath('TEN123', 'DOC456', 1, 'pdf')
     * // Returns: "TEN123/2025/11/DOC456/v1.pdf"
     */
    public function generateStoragePath(
        string $tenantId,
        string $uuid,
        int $version,
        string $extension,
        ?\DateTimeInterface $date = null
    ): string {
        $date = $date ?? new \DateTimeImmutable();
        $year = $date->format('Y');
        $month = $date->format('m');

        return sprintf(
            '%s/%s/%s/%s/v%d.%s',
            $tenantId,
            $year,
            $month,
            $uuid,
            $version,
            ltrim($extension, '.')
        );
    }

    /**
     * Parse a storage path into its components.
     *
     * @param string $path Storage path
     * @return array{tenant: string, year: string, month: string, uuid: string, version: int, extension: string}
     * @throws \InvalidArgumentException If path format is invalid
     *
     * @example
     * parseStoragePath('TEN123/2025/11/DOC456/v1.pdf')
     * // Returns: ['tenant' => 'TEN123', 'year' => '2025', 'month' => '11', 'uuid' => 'DOC456', 'version' => 1, 'extension' => 'pdf']
     */
    public function parseStoragePath(string $path): array
    {
        $pattern = '/^([^\/]+)\/(\d{4})\/(\d{2})\/([^\/]+)\/v(\d+)\.(.+)$/';

        if (!preg_match($pattern, $path, $matches)) {
            throw new \InvalidArgumentException("Invalid storage path format: {$path}");
        }

        return [
            'tenant' => $matches[1],
            'year' => $matches[2],
            'month' => $matches[3],
            'uuid' => $matches[4],
            'version' => (int) $matches[5],
            'extension' => $matches[6],
        ];
    }

    /**
     * Generate a new version path based on an existing path.
     *
     * @param string $basePath Existing document path
     * @param int $newVersion New version number
     * @return string New version path
     * @throws \InvalidArgumentException If base path format is invalid
     *
     * @example
     * getVersionPath('TEN123/2025/11/DOC456/v1.pdf', 2)
     * // Returns: "TEN123/2025/11/DOC456/v2.pdf"
     */
    public function getVersionPath(string $basePath, int $newVersion): string
    {
        $components = $this->parseStoragePath($basePath);

        return $this->generateStoragePath(
            $components['tenant'],
            $components['uuid'],
            $newVersion,
            $components['extension'],
            new \DateTimeImmutable("{$components['year']}-{$components['month']}-01")
        );
    }

    /**
     * Get the directory path for a document (without version/filename).
     *
     * @param string $tenantId Tenant ULID
     * @param string $uuid Document ULID
     * @param \DateTimeInterface|null $date Date for year/month (defaults to now)
     * @return string Directory path
     *
     * @example
     * getDocumentDirectory('TEN123', 'DOC456')
     * // Returns: "TEN123/2025/11/DOC456"
     */
    public function getDocumentDirectory(
        string $tenantId,
        string $uuid,
        ?\DateTimeInterface $date = null
    ): string {
        $date = $date ?? new \DateTimeImmutable();
        $year = $date->format('Y');
        $month = $date->format('m');

        return sprintf('%s/%s/%s/%s', $tenantId, $year, $month, $uuid);
    }

    /**
     * Get the archive prefix for a specific year/month (for lifecycle policies).
     *
     * @param string $tenantId Tenant ULID
     * @param int $year Year (e.g., 2025)
     * @param int $month Month (1-12)
     * @return string Archive prefix
     *
     * @example
     * getArchivePrefix('TEN123', 2023, 1)
     * // Returns: "TEN123/2023/01"
     */
    public function getArchivePrefix(string $tenantId, int $year, int $month): string
    {
        return sprintf('%s/%04d/%02d', $tenantId, $year, $month);
    }

    /**
     * Get year and month from a storage path.
     *
     * @param string $path Storage path
     * @return array{year: int, month: int}
     * @throws \InvalidArgumentException If path format is invalid
     */
    public function extractDate(string $path): array
    {
        $components = $this->parseStoragePath($path);

        return [
            'year' => (int) $components['year'],
            'month' => (int) $components['month'],
        ];
    }

    /**
     * Check if a path belongs to a specific tenant.
     *
     * @param string $path Storage path
     * @param string $tenantId Tenant ULID
     */
    public function belongsToTenant(string $path, string $tenantId): bool
    {
        try {
            $components = $this->parseStoragePath($path);
            return $components['tenant'] === $tenantId;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
