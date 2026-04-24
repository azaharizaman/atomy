<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;

final readonly class AwardDebriefDraftRequest
{
    /**
     * @param array<string, mixed> $award
     * @param array<string, mixed> $losingVendor
     * @param array<string, mixed> $comparisonContext
     */
    public function __construct(
        public string $tenantId,
        public string $awardId,
        public string $rfqId,
        public string $comparisonRunId,
        public string $vendorId,
        public array $award,
        public array $losingVendor,
        public array $comparisonContext,
    ) {
        if (
            trim($tenantId) === ''
            || trim($awardId) === ''
            || trim($rfqId) === ''
            || trim($comparisonRunId) === ''
            || trim($vendorId) === ''
        ) {
            throw new InvalidArgumentException('Award debrief draft request requires tenantId, awardId, rfqId, comparisonRunId, and vendorId.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'award_id' => $this->awardId,
            'rfq_id' => $this->rfqId,
            'comparison_run_id' => $this->comparisonRunId,
            'vendor_id' => $this->vendorId,
            'award' => $this->award,
            'losing_vendor' => $this->losingVendor,
            'comparison_context' => $this->comparisonContext,
        ];
    }
}
