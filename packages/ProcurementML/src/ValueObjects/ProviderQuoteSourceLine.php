<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use Nexus\ProcurementML\Enums\SourceLineOrigin;
use Nexus\ProcurementML\Exceptions\ProcurementMlContractException;

final readonly class ProviderQuoteSourceLine
{
    /**
     * @param array<string, scalar|null> $reliabilityHints
     */
    public function __construct(
        public string $sourceLineId,
        public int $lineNumber,
        public string $description,
        public float $quantity,
        public string $unitOfMeasure,
        public ?float $unitPrice,
        public string $currency,
        public string $rawText,
        public SourceLineOrigin $origin,
        public ?float $confidence = null,
        public array $reliabilityHints = [],
    ) {
        $this->assertNonEmpty($this->sourceLineId, 'source line id');
        $this->assertNonEmpty($this->description, 'source line description');
        $this->assertNonEmpty($this->unitOfMeasure, 'source line unit of measure');
        $this->assertNonEmpty($this->currency, 'source line currency');

        if ($this->lineNumber < 1) {
            throw ProcurementMlContractException::invalidValue('source line number');
        }

        if ($this->quantity < 0.0) {
            throw ProcurementMlContractException::invalidValue('source line quantity');
        }

        if ($this->unitPrice !== null && $this->unitPrice < 0.0) {
            throw ProcurementMlContractException::invalidValue('source line unit price');
        }

        if ($this->confidence !== null && ($this->confidence < 0.0 || $this->confidence > 1.0)) {
            throw ProcurementMlContractException::invalidValue('source line confidence');
        }

        $this->assertScalarMap($this->reliabilityHints, 'source line reliability hints');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromProviderPayload(array $payload): ?self
    {
        $required = [
            'source_line_id',
            'line_number',
            'description',
            'quantity',
            'unit_of_measure',
            'currency',
        ];

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                return null;
            }
        }

        if (!is_string($payload['source_line_id']) || self::integerValue($payload['line_number']) === null) {
            return null;
        }

        if (!is_string($payload['description']) || trim($payload['description']) === '') {
            return null;
        }

        if (!is_numeric($payload['quantity']) || !is_string($payload['unit_of_measure'])) {
            return null;
        }

        if (!is_string($payload['currency'])) {
            return null;
        }

        $unitPrice = $payload['unit_price'] ?? null;
        if ($unitPrice !== null && !is_numeric($unitPrice)) {
            return null;
        }

        $rawText = $payload['raw_text'] ?? '';
        if (!is_string($rawText)) {
            return null;
        }

        $confidence = $payload['confidence'] ?? null;
        if ($confidence !== null && !is_numeric($confidence)) {
            return null;
        }

        $reliabilityHints = $payload['reliability_hints'] ?? [];
        if (!is_array($reliabilityHints) || !self::isScalarMap($reliabilityHints)) {
            return null;
        }

        try {
            return new self(
                sourceLineId: $payload['source_line_id'],
                lineNumber: self::integerValue($payload['line_number']) ?? 0,
                description: $payload['description'],
                quantity: (float) $payload['quantity'],
                unitOfMeasure: $payload['unit_of_measure'],
                unitPrice: $unitPrice === null ? null : (float) $unitPrice,
                currency: $payload['currency'],
                rawText: $rawText,
                origin: SourceLineOrigin::PROVIDER,
                confidence: $confidence === null ? null : (float) $confidence,
                reliabilityHints: $reliabilityHints,
            );
        } catch (ProcurementMlContractException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_line_id' => $this->sourceLineId,
            'line_number' => $this->lineNumber,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_of_measure' => $this->unitOfMeasure,
            'unit_price' => $this->unitPrice,
            'currency' => $this->currency,
            'raw_text' => $this->rawText,
            'origin' => $this->origin->value,
            'confidence' => $this->confidence,
            'reliability_hints' => $this->reliabilityHints,
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
        if (!self::isScalarMap($values)) {
            throw ProcurementMlContractException::invalidValue($subject);
        }
    }

    /**
     * @param array<mixed> $values
     */
    private static function isScalarMap(array $values): bool
    {
        foreach ($values as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                return false;
            }

            if (!is_scalar($value) && $value !== null) {
                return false;
            }
        }

        return true;
    }

    private static function integerValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        return null;
    }
}
