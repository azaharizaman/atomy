<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;

final readonly class ApprovalSummaryRequest
{
    /**
     * @param array<string, mixed> $approval
     * @param array<string, mixed> $comparisonContext
     */
    public function __construct(
        public string $tenantId,
        public string $approvalId,
        public string $rfqId,
        public string $comparisonRunId,
        public array $approval,
        public array $comparisonContext,
    ) {
        if (trim($tenantId) === '' || trim($approvalId) === '' || trim($rfqId) === '' || trim($comparisonRunId) === '') {
            throw new InvalidArgumentException('Approval summary request requires tenantId, approvalId, rfqId, and comparisonRunId.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'approval_id' => $this->approvalId,
            'rfq_id' => $this->rfqId,
            'comparison_run_id' => $this->comparisonRunId,
            'approval' => $this->approval,
            'comparison_context' => $this->comparisonContext,
        ];
    }
}
