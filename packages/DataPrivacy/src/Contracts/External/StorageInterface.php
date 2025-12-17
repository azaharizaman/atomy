<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts\External;

/**
 * External interface for storing data exports and breach evidence.
 *
 * This interface must be implemented by the consuming application,
 * typically using the Nexus\Storage package.
 */
interface StorageInterface
{
    /**
     * Store data export for download.
     *
     * @param string $requestId Related request ID
     * @param string $content Export content
     * @param string $format Export format (json, csv, xml)
     * @return string URL or path to access the file
     */
    public function storeExport(
        string $requestId,
        string $content,
        string $format
    ): string;

    /**
     * Store breach evidence/documentation.
     *
     * @param string $breachId Related breach ID
     * @param string $content Evidence content
     * @param string $filename Original filename
     * @return string Storage reference
     */
    public function storeBreachEvidence(
        string $breachId,
        string $content,
        string $filename
    ): string;

    /**
     * Delete stored export.
     */
    public function deleteExport(string $requestId): void;

    /**
     * Get export download URL with expiry.
     *
     * @param string $requestId Request ID
     * @param int $expiryMinutes Minutes until URL expires
     * @return string Temporary download URL
     */
    public function getExportUrl(string $requestId, int $expiryMinutes = 60): string;

    /**
     * Check if export exists.
     */
    public function exportExists(string $requestId): bool;
}
