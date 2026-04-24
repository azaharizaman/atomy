<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;

final readonly class AwardGuidanceRequest
{
    /**
     * @param array<string, mixed> $award
     * @param array<string, mixed> $comparisonContext
     */
    public function __construct(
        public string $tenantId,
        public string $awardId,
        public string $rfqId,
        public string $comparisonRunId,
        public array $award,
        public array $comparisonContext,
    ) {
        if (trim($tenantId) === '' || trim($awardId) === '' || trim($rfqId) === '' || trim($comparisonRunId) === '') {
            throw new InvalidArgumentException('Award guidance request requires tenantId, awardId, rfqId, and comparisonRunId.');
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
            'award' => $this->award,
            'comparison_context' => $this->comparisonContext,
        ];
    }
}
