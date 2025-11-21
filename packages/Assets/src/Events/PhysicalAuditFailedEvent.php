<?php

declare(strict_types=1);

namespace Nexus\Assets\Events;

/**
 * Physical Audit Failed Event (HIGH severity, Tier 3)
 *
 * Published when asset verification fails during physical audit.
 */
final readonly class PhysicalAuditFailedEvent
{
    public function __construct(
        public string $assetId,
        public string $tenantId,
        public string $assetTag,
        public string $expectedLocation,
        public ?string $actualLocation,
        public string $auditedBy,
        public ?string $discrepancyNotes = null
    ) {}

    /**
     * Convert to array for audit logging
     */
    public function toArray(): array
    {
        return [
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'asset_tag' => $this->assetTag,
            'expected_location' => $this->expectedLocation,
            'actual_location' => $this->actualLocation,
            'audited_by' => $this->auditedBy,
            'discrepancy_notes' => $this->discrepancyNotes,
        ];
    }
}
