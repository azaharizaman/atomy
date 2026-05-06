<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\Enums\ComparisonType;
use Nexus\MetricEngine\Enums\RoundingMode;
use Nexus\MetricEngine\Exceptions\FormulaSerializationException;
use Nexus\MetricEngine\ValueObjects\ComparisonDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaDefinition;
use Nexus\MetricEngine\ValueObjects\FormulaReference;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

class FormulaDefinitionSerializerService
{
    /** @return array<string, mixed> */
    public function toArray(FormulaDefinition $formula): array
    {
        return [
            'identifier' => $formula->identifier(),
            'operation' => $formula->operation()->value,
            'operands' => array_map(fn (mixed $operand): mixed => $this->serializeOperand($operand), $formula->operands()),
            'precision' => [
                'scale' => $formula->precisionPolicy()->scale,
                'rounding_mode' => $formula->precisionPolicy()->roundingMode->value,
            ],
            'window' => $this->serializeWindow($formula->window()),
            'comparison' => $this->serializeComparison($formula->comparison()),
            'unit' => $formula->unit(),
            'metadata' => $formula->metadata(),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function fromArray(array $payload): FormulaDefinition
    {
        $identifier = $this->requireString($payload, 'identifier', 'Serialized formula requires identifier.');
        $operationValue = $this->requireString($payload, 'operation', 'Serialized formula requires operation.');
        $operation = AggregationType::tryFrom($operationValue);

        if ($operation === null) {
            throw new FormulaSerializationException("Unsupported formula operation [{$operationValue}].");
        }

        if (! isset($payload['operands']) || ! is_array($payload['operands'])) {
            throw new FormulaSerializationException('Serialized formula requires operands array.');
        }

        return new FormulaDefinition(
            identifier: $identifier,
            operation: $operation,
            operands: array_map(fn (mixed $operand): mixed => $this->deserializeOperand($operand), array_values($payload['operands'])),
            precisionPolicy: $this->deserializePrecision($payload['precision'] ?? []),
            window: $this->deserializeWindow($payload['window'] ?? null),
            comparison: $this->deserializeComparison($payload['comparison'] ?? null),
            unit: isset($payload['unit']) && is_string($payload['unit']) ? $payload['unit'] : null,
            metadata: isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : []
        );
    }

    private function serializeOperand(mixed $operand): mixed
    {
        if ($operand instanceof FormulaDefinition) {
            return $this->toArray($operand);
        }

        if ($operand instanceof FormulaReference) {
            return ['formula' => $operand->identifier];
        }

        if (is_array($operand)) {
            return array_map(fn (mixed $nested): mixed => $this->serializeOperand($nested), $operand);
        }

        return $operand;
    }

    private function deserializeOperand(mixed $operand): mixed
    {
        if (is_array($operand) && $this->isFormulaPayload($operand)) {
            return $this->fromArray($operand);
        }

        if (is_array($operand) && array_key_exists('formula', $operand)) {
            if (! is_string($operand['formula'])) {
                throw new FormulaSerializationException('Formula reference must contain a string identifier.');
            }

            return new FormulaReference($operand['formula']);
        }

        if (is_array($operand)) {
            return array_map(fn (mixed $nested): mixed => $this->deserializeOperand($nested), $operand);
        }

        return $operand;
    }

    /** @param array<mixed> $payload */
    private function isFormulaPayload(array $payload): bool
    {
        return array_key_exists('identifier', $payload)
            && array_key_exists('operation', $payload)
            && array_key_exists('operands', $payload);
    }

    /** @param array<string, mixed>|mixed $payload */
    private function deserializePrecision(mixed $payload): PrecisionPolicy
    {
        if (! is_array($payload)) {
            throw new FormulaSerializationException('Serialized formula precision must be an array.');
        }

        $scale = isset($payload['scale']) && is_int($payload['scale']) ? $payload['scale'] : 2;
        $roundingValue = isset($payload['rounding_mode']) && is_string($payload['rounding_mode'])
            ? $payload['rounding_mode']
            : RoundingMode::HALF_UP->value;

        $roundingMode = RoundingMode::tryFrom($roundingValue);

        if ($roundingMode === null) {
            throw new FormulaSerializationException("Unsupported rounding mode [{$roundingValue}].");
        }

        return new PrecisionPolicy($scale, $roundingMode);
    }

    /** @return array<string, mixed>|null */
    private function serializeWindow(?TimeWindow $window): ?array
    {
        if ($window === null) {
            return null;
        }

        return [
            'type' => $window->type->value,
            'size' => $window->size,
            'start_period' => $window->startPeriod,
            'end_period' => $window->endPeriod,
        ];
    }

    /** @param array<string, mixed>|mixed $payload */
    private function deserializeWindow(mixed $payload): ?TimeWindow
    {
        if ($payload === null) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['type']) || ! is_string($payload['type'])) {
            throw new FormulaSerializationException('Serialized window requires type.');
        }

        return match ($payload['type']) {
            'fixed_rolling' => TimeWindow::fixedRolling((int) ($payload['size'] ?? 0)),
            'explicit_range' => TimeWindow::explicitRange(
                (string) ($payload['start_period'] ?? ''),
                (string) ($payload['end_period'] ?? '')
            ),
            default => throw new FormulaSerializationException("Unsupported window type [{$payload['type']}]."),
        };
    }

    /** @return array<string, mixed>|null */
    private function serializeComparison(?ComparisonDefinition $comparison): ?array
    {
        if ($comparison === null) {
            return null;
        }

        return ['type' => $comparison->type->value];
    }

    /** @param array<string, mixed>|mixed $payload */
    private function deserializeComparison(mixed $payload): ?ComparisonDefinition
    {
        if ($payload === null) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['type']) || ! is_string($payload['type'])) {
            throw new FormulaSerializationException('Serialized comparison requires type.');
        }

        $type = ComparisonType::tryFrom($payload['type']);

        if ($type === null) {
            throw new FormulaSerializationException("Unsupported comparison type [{$payload['type']}].");
        }

        return new ComparisonDefinition($type);
    }

    /** @param array<string, mixed> $payload */
    private function requireString(array $payload, string $key, string $message): string
    {
        if (! isset($payload[$key]) || ! is_string($payload[$key]) || trim($payload[$key]) === '') {
            throw new FormulaSerializationException($message);
        }

        return $payload[$key];
    }
}
