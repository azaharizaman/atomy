<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;

final readonly class GovernanceNarrativeRequest
{
    /**
     * @param array<string, mixed> $facts
     */
    public function __construct(
        public string $featureKey,
        public string $tenantId,
        public string $vendorId,
        public array $facts,
    ) {
        if (trim($this->featureKey) === '' || trim($this->tenantId) === '' || trim($this->vendorId) === '') {
            throw new InvalidArgumentException('Governance narrative request requires featureKey, tenantId, and vendorId.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'feature_key' => $this->featureKey,
            'tenant_id' => $this->tenantId,
            'vendor_id' => $this->vendorId,
            'facts' => $this->facts,
        ];
    }
}
