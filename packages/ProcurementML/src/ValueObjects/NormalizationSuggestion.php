<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use Nexus\ProcurementML\Enums\SourceLineOrigin;
use Nexus\ProcurementML\Exceptions\ProcurementMlContractException;

final readonly class NormalizationSuggestion
{
    /**
     * @param array<string, scalar|null> $reliabilityHints
     */
    public function __construct(
        public string $sourceLineId,
        public string $rfqLineId,
        public SourceLineOrigin $origin,
        public string $normalizedDescription,
        public float $normalizedQuantity,
        public string $normalizedUnitOfMeasure,
        public ?string $taxonomyCode = null,
        public ?float $confidence = null,
        public array $reliabilityHints = [],
        public ?string $explanation = null,
        public ?ProviderAiProvenance $provenance = null,
    ) {
        $this->assertNonEmpty($this->sourceLineId, 'normalization source line id');
        $this->assertNonEmpty($this->rfqLineId, 'normalization RFQ line id');
        $this->assertNonEmpty($this->normalizedDescription, 'normalization description');
        $this->assertNonEmpty($this->normalizedUnitOfMeasure, 'normalization unit of measure');

        if ($this->origin === SourceLineOrigin::PROVIDER && $this->provenance === null) {
            throw ProcurementMlContractException::invalidValue('provider normalization provenance');
        }

        if ($this->normalizedQuantity < 0.0) {
            throw ProcurementMlContractException::invalidValue('normalization quantity');
        }

        if ($this->confidence !== null && ($this->confidence < 0.0 || $this->confidence > 1.0)) {
            throw ProcurementMlContractException::invalidValue('normalization confidence');
        }

        $this->assertScalarMap($this->reliabilityHints, 'normalization reliability hints');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_line_id' => $this->sourceLineId,
            'rfq_line_id' => $this->rfqLineId,
            'origin' => $this->origin->value,
            'normalized_description' => $this->normalizedDescription,
            'normalized_quantity' => $this->normalizedQuantity,
            'normalized_unit_of_measure' => $this->normalizedUnitOfMeasure,
            'taxonomy_code' => $this->taxonomyCode,
            'confidence' => $this->confidence,
            'reliability_hints' => $this->reliabilityHints,
            'explanation' => $this->explanation,
            'provenance' => $this->provenance?->toArray(),
        ];
    }

    private function assertNonEmpty(string $value, string $subject): void
    {
        if (trim($value) === '') {
            throw ProcurementMlContractException::invalidValue($subject);
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function assertScalarMap(array $values, string $subject): void
    {
        foreach ($values as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw ProcurementMlContractException::invalidValue($subject);
            }

            if (!is_scalar($value) && $value !== null) {
                throw ProcurementMlContractException::invalidValue($subject);
            }
        }
    }
}
