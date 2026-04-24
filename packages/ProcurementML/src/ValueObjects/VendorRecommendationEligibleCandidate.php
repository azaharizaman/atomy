<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use Nexus\ProcurementML\Exceptions\ProcurementMlContractException;

final readonly class VendorRecommendationEligibleCandidate
{
    /**
     * @param list<string> $deterministicReasons
     * @param list<string> $llmInsights
     * @param list<string> $warningFlags
     * @param list<string> $warnings
     */
    public function __construct(
        public string $vendorId,
        public string $vendorName,
        public int $fitScore,
        public string $confidenceBand,
        public string $providerExplanation,
        public array $deterministicReasons,
        public array $llmInsights = [],
        public array $warningFlags = [],
        public array $warnings = [],
    ) {
        $this->assertNonEmpty($this->vendorId, 'eligible vendor id');
        $this->assertNonEmpty($this->vendorName, 'eligible vendor name');
        $this->assertNonEmpty($this->confidenceBand, 'eligible confidence band');
        $this->assertNonEmpty($this->providerExplanation, 'eligible provider explanation');

        if ($this->fitScore < 0 || $this->fitScore > 100) {
            throw ProcurementMlContractException::invalidValue('eligible candidate fit score');
        }

        $this->assertList($this->deterministicReasons, 'eligible candidate deterministic reasons');
        $this->assertList($this->llmInsights, 'eligible candidate LLM insights');
        $this->assertList($this->warningFlags, 'eligible candidate warning flags');
        $this->assertList($this->warnings, 'eligible candidate warnings');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'fit_score' => $this->fitScore,
            'confidence_band' => $this->confidenceBand,
            'provider_explanation' => $this->providerExplanation,
            'deterministic_reasons' => array_values(array_unique($this->normalizeList($this->deterministicReasons))),
            'llm_insights' => array_values(array_unique($this->normalizeList($this->llmInsights))),
            'warning_flags' => array_values(array_unique($this->normalizeList($this->warningFlags))),
            'warnings' => array_values(array_unique($this->normalizeList($this->warnings))),
        ];
    }

    private function assertNonEmpty(string $value, string $subject): void
    {
        if (trim($value) === '') {
            throw ProcurementMlContractException::invalidValue($subject);
        }
    }

    /**
     * @param list<string> $values
     */
    private function assertList(array $values, string $subject): void
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
