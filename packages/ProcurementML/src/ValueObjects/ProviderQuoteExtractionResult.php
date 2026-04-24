<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\ValueObjects;

use Nexus\ProcurementML\Enums\ProviderResultStatus;
use Nexus\ProcurementML\Exceptions\ProcurementMlContractException;

final readonly class ProviderQuoteExtractionResult
{
    /**
     * @param list<ProviderQuoteSourceLine> $sourceLines
     * @param array<string, scalar|null> $commercialTerms
     */
    private function __construct(
        public ProviderResultStatus $status,
        public array $sourceLines,
        public array $commercialTerms,
        public ?ProviderAiProvenance $provenance,
        public ?string $unavailableReason,
    ) {
        if ($this->status === ProviderResultStatus::AVAILABLE && $this->sourceLines === []) {
            throw ProcurementMlContractException::invalidValue('available quote extraction source lines');
        }

        if ($this->status !== ProviderResultStatus::AVAILABLE && $this->sourceLines !== []) {
            throw ProcurementMlContractException::invalidValue('unavailable quote extraction source lines');
        }

        foreach ($this->sourceLines as $sourceLine) {
            if (!$sourceLine instanceof ProviderQuoteSourceLine) {
                throw ProcurementMlContractException::invalidValue('quote extraction source lines');
            }
        }

        $this->assertScalarMap($this->commercialTerms, 'quote extraction commercial terms');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromProviderPayload(array $payload, ProviderAiProvenance $provenance): self
    {
        $sourceLines = $payload['source_lines'] ?? null;
        if (!is_array($sourceLines) || !self::isList($sourceLines) || $sourceLines === []) {
            return self::manualActionRequired($provenance, 'provider_payload_malformed');
        }

        $parsedLines = [];
        foreach ($sourceLines as $sourceLine) {
            if (!is_array($sourceLine)) {
                return self::manualActionRequired($provenance, 'provider_payload_malformed');
            }

            $parsedLine = ProviderQuoteSourceLine::fromProviderPayload($sourceLine);
            if ($parsedLine === null) {
                return self::manualActionRequired($provenance, 'provider_payload_malformed');
            }

            $parsedLines[] = $parsedLine;
        }

        $commercialTerms = $payload['commercial_terms'] ?? [];
        if (!is_array($commercialTerms) || !self::isScalarMapStatic($commercialTerms)) {
            return self::manualActionRequired($provenance, 'provider_payload_malformed');
        }

        return new self(
            status: ProviderResultStatus::AVAILABLE,
            sourceLines: $parsedLines,
            commercialTerms: $commercialTerms,
            provenance: $provenance,
            unavailableReason: null,
        );
    }

    public static function unavailable(?ProviderAiProvenance $provenance, string $reason): self
    {
        return new self(
            status: ProviderResultStatus::UNAVAILABLE,
            sourceLines: [],
            commercialTerms: [],
            provenance: $provenance,
            unavailableReason: $reason,
        );
    }

    public static function manualActionRequired(?ProviderAiProvenance $provenance, string $reason): self
    {
        return new self(
            status: ProviderResultStatus::MANUAL_ACTION_REQUIRED,
            sourceLines: [],
            commercialTerms: [],
            provenance: $provenance,
            unavailableReason: $reason,
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === ProviderResultStatus::AVAILABLE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'source_lines' => array_map(
                static fn (ProviderQuoteSourceLine $sourceLine): array => $sourceLine->toArray(),
                $this->sourceLines,
            ),
            'commercial_terms' => $this->commercialTerms,
            'provenance' => $this->provenance?->toArray(),
            'unavailable_reason' => $this->unavailableReason,
        ];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function assertScalarMap(array $values, string $subject): void
    {
        if (!self::isScalarMapStatic($values)) {
            throw ProcurementMlContractException::invalidValue($subject);
        }
    }

    /**
     * @param array<mixed> $values
     */
    private static function isScalarMapStatic(array $values): bool
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

    /**
     * @param array<mixed> $values
     */
    private static function isList(array $values): bool
    {
        return array_keys($values) === range(0, count($values) - 1);
    }
}
