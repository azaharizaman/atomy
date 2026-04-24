<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;

final readonly class AwardGuidanceRequest
{
    public string $tenantId;
    public string $awardId;
    public string $rfqId;
    public string $comparisonRunId;
    /** @var array<string, mixed> */
    public array $award;
    /** @var array<string, mixed> */
    public array $comparisonContext;

    /**
     * @param array<string, mixed> $award
     * @param array<string, mixed> $comparisonContext
     */
    public function __construct(
        string $tenantId,
        string $awardId,
        string $rfqId,
        string $comparisonRunId,
        array $award,
        array $comparisonContext,
    ) {
        $tenantId = trim($tenantId);
        $awardId = trim($awardId);
        $rfqId = trim($rfqId);
        $comparisonRunId = trim($comparisonRunId);

        if ($tenantId === '' || $awardId === '' || $rfqId === '' || $comparisonRunId === '') {
            throw new InvalidArgumentException('Award guidance request requires tenantId, awardId, rfqId, and comparisonRunId.');
        }

        $this->tenantId = $tenantId;
        $this->awardId = $awardId;
        $this->rfqId = $rfqId;
        $this->comparisonRunId = $comparisonRunId;
        $this->award = $award;
        $this->comparisonContext = $comparisonContext;
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
