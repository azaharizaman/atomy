<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;

final readonly class InsightSummaryRequest
{
    public string $featureKey;

    public string $tenantId;

    public string $subjectType;

    /**
     * @param array<string, mixed> $facts
     */
    public function __construct(
        string $featureKey,
        string $tenantId,
        string $subjectType,
        public array $facts,
    ) {
        $this->featureKey = trim($featureKey);
        $this->tenantId = trim($tenantId);
        $this->subjectType = trim($subjectType);

        if ($this->featureKey === '' || $this->tenantId === '' || $this->subjectType === '') {
            throw new InvalidArgumentException('Insight summary request requires featureKey, tenantId, and subjectType.');
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
            'subject_type' => $this->subjectType,
            'facts' => $this->facts,
        ];
    }
}
