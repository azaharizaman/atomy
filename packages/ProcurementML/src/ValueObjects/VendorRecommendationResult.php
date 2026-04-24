<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use Nexus\ProcurementML\Enums\VendorRecommendationResultStatus;
use Nexus\ProcurementML\Exceptions\ProcurementMlContractException;

final readonly class VendorRecommendationResult
{
    /** @var list<string> */
    public array $deterministicReasonSet;

    /**
     * @param list<VendorRecommendationEligibleCandidate> $eligibleCandidates
     * @param list<VendorRecommendationExcludedCandidate> $excludedCandidates
     * @param list<string> $deterministicReasonSet
     */
    private function __construct(
        public string $tenantId,
        public string $rfqId,
        public VendorRecommendationResultStatus $status,
        public array $eligibleCandidates,
        public array $excludedCandidates,
        public ?string $providerExplanation,
        array $deterministicReasonSet,
        public ?ProviderAiProvenance $provenance,
        public ?string $unavailableReason,
    ) {
        $this->assertNonEmpty($this->tenantId, 'vendor recommendation tenant id');
        $this->assertNonEmpty($this->rfqId, 'vendor recommendation RFQ id');
        $this->assertList($this->eligibleCandidates, VendorRecommendationEligibleCandidate::class, 'eligible candidates');
        $this->assertList($this->excludedCandidates, VendorRecommendationExcludedCandidate::class, 'excluded candidates');
        $this->assertStringList($deterministicReasonSet, 'deterministic reason set');
        $this->deterministicReasonSet = array_values(array_unique($this->normalizeList($deterministicReasonSet)));

        if ($this->status === VendorRecommendationResultStatus::AVAILABLE) {
            if ($this->provenance === null) {
                throw ProcurementMlContractException::invalidValue('vendor recommendation provenance');
            }

            if ($this->unavailableReason !== null) {
                throw ProcurementMlContractException::invalidValue('available vendor recommendation unavailable reason');
            }
        } elseif ($this->unavailableReason === null) {
            throw ProcurementMlContractException::invalidValue('unavailable vendor recommendation reason');
        }
    }

    /**
     * @param list<VendorRecommendationEligibleCandidate> $eligibleCandidates
     * @param list<VendorRecommendationExcludedCandidate> $excludedCandidates
     * @param list<string> $deterministicReasonSet
     */
    public static function available(
        string $tenantId,
        string $rfqId,
        array $eligibleCandidates,
        array $excludedCandidates,
        ?string $providerExplanation,
        array $deterministicReasonSet,
        ProviderAiProvenance $provenance,
    ): self {
        return new self(
            tenantId: $tenantId,
            rfqId: $rfqId,
            status: VendorRecommendationResultStatus::AVAILABLE,
            eligibleCandidates: $eligibleCandidates,
            excludedCandidates: $excludedCandidates,
            providerExplanation: $providerExplanation,
            deterministicReasonSet: $deterministicReasonSet,
            provenance: $provenance,
            unavailableReason: null,
        );
    }

    /**
     * @param list<string> $deterministicReasonSet
     */
    public static function unavailable(
        string $tenantId,
        string $rfqId,
        string $reason,
        array $deterministicReasonSet = [],
        ?ProviderAiProvenance $provenance = null,
    ): self {
        return new self(
            tenantId: $tenantId,
            rfqId: $rfqId,
            status: VendorRecommendationResultStatus::UNAVAILABLE,
            eligibleCandidates: [],
            excludedCandidates: [],
            providerExplanation: null,
            deterministicReasonSet: $deterministicReasonSet,
            provenance: $provenance,
            unavailableReason: $reason,
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === VendorRecommendationResultStatus::AVAILABLE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'rfq_id' => $this->rfqId,
            'status' => $this->status->value,
            'eligible_candidates' => array_map(
                static fn (VendorRecommendationEligibleCandidate $candidate): array => $candidate->toArray(),
                $this->eligibleCandidates,
            ),
            'excluded_candidates' => array_map(
                static fn (VendorRecommendationExcludedCandidate $candidate): array => $candidate->toArray(),
                $this->excludedCandidates,
            ),
            'provider_explanation' => $this->providerExplanation,
            'deterministic_reason_set' => $this->deterministicReasonSet,
            'provenance' => $this->provenance?->toArray(),
            'unavailable_reason' => $this->unavailableReason,
        ];
    }

    private function assertNonEmpty(string $value, string $subject): void
    {
        if (trim($value) === '') {
            throw ProcurementMlContractException::invalidValue($subject);
        }
    }

    /**
     * @param array<mixed> $values
     */
    private function assertList(array $values, string $expectedClass, string $subject): void
    {
        foreach ($values as $value) {
            if (!$value instanceof $expectedClass) {
                throw ProcurementMlContractException::invalidValue($subject);
            }
        }
    }

    /**
     * @param list<string> $values
     */
    private function assertStringList(array $values, string $subject): void
    {
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw ProcurementMlContractException::invalidValue($subject);
            }
        }
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function normalizeList(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $value = trim($value);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }
}
