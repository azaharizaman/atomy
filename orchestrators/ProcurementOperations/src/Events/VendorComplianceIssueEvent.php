<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\ProcurementOperations\Enums\VendorHoldReason;

/**
 * Event published when a vendor compliance issue is detected.
 *
 * This event can trigger workflow automation for compliance resolution.
 */
final readonly class VendorComplianceIssueEvent
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param array<VendorHoldReason> $issues Compliance issues detected
     * @param \DateTimeImmutable $detectedAt When the issue was detected
     * @param array<string, mixed> $details Additional issue details
     */
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public array $issues,
        public \DateTimeImmutable $detectedAt,
        public array $details = [],
    ) {}

    /**
     * Check if any issue is a hard block.
     */
    public function hasHardBlockIssue(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->isHardBlock()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get count of issues.
     */
    public function getIssueCount(): int
    {
        return count($this->issues);
    }
}
