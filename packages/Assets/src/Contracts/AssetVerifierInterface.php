<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;

/**
 * Asset Verifier Interface (Tier 3)
 *
 * Manages physical audit scheduling and verification.
 */
interface AssetVerifierInterface
{
    /**
     * Schedule physical audit
     *
     * @param string $assetId Asset identifier
     * @param DateTimeInterface $scheduledDate Scheduled audit date
     * @param string|null $assignedTo User assigned to perform audit
     * @return void
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function scheduleAudit(
        string $assetId,
        DateTimeInterface $scheduledDate,
        ?string $assignedTo = null
    ): void;

    /**
     * Record audit result
     *
     * @param string $assetId Asset identifier
     * @param bool $verified True if asset found and verified
     * @param string|null $actualLocation Actual location found (if different from expected)
     * @param string|null $notes Audit notes
     * @return void
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function recordAuditResult(
        string $assetId,
        bool $verified,
        ?string $actualLocation = null,
        ?string $notes = null
    ): void;

    /**
     * Get pending audits
     *
     * @param string $tenantId Tenant identifier
     * @return array Array of pending audit records
     */
    public function getPendingAudits(string $tenantId): array;

    /**
     * Get audit history for asset
     *
     * @param string $assetId Asset identifier
     * @return array Array of audit records
     */
    public function getAuditHistory(string $assetId): array;

    /**
     * Get assets with failed audits
     *
     * @param string $tenantId Tenant identifier
     * @return array Array of assets with verification failures
     */
    public function getFailedAudits(string $tenantId): array;
}
