<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use Nexus\ProcurementML\Exceptions\ProcurementMlContractException;

final readonly class VendorRecommendationEligibleCandidate
{
    /** @var list<string> */
    public array $deterministicReasons;

    /** @var list<string> */
    public array $llmInsights;

    /** @var list<string> */
    public array $warningFlags;

    /** @var list<string> */
    public array $warnings;

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
        array $deterministicReasons,
        array $llmInsights = [],
        array $warningFlags = [],
        array $warnings = [],
    ) {
        $this->assertNonEmpty($this->vendorId, 'eligible vendor id');
        $this->assertNonEmpty($this->vendorName, 'eligible vendor name');
        $this->assertNonEmpty($this->confidenceBand, 'eligible confidence band');
        $this->assertNonEmpty($this->providerExplanation, 'eligible provider explanation');

        if ($this->fitScore < 0 || $this->fitScore > 100) {
            throw ProcurementMlContractException::invalidValue('eligible candidate fit score');
        }

        $this->assertList($deterministicReasons, 'eligible candidate deterministic reasons');
        $this->assertList($llmInsights, 'eligible candidate LLM insights');
        $this->assertList($warningFlags, 'eligible candidate warning flags');
        $this->assertList($warnings, 'eligible candidate warnings');

        $this->deterministicReasons = $this->normalizeList($deterministicReasons);
        $this->llmInsights = $this->normalizeList($llmInsights);
        $this->warningFlags = $this->normalizeList($warningFlags);
        $this->warnings = $this->normalizeList($warnings);
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
            'deterministic_reasons' => array_values(array_unique($this->deterministicReasons)),
            'llm_insights' => array_values(array_unique($this->llmInsights)),
            'warning_flags' => array_values(array_unique($this->warningFlags)),
            'warnings' => array_values(array_unique($this->warnings)),
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
