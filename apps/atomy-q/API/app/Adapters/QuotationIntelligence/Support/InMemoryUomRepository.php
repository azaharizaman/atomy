<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence\Support;

use Nexus\Uom\Contracts\ConversionRuleInterface;
use Nexus\Uom\Contracts\DimensionInterface;
use Nexus\Uom\Contracts\UnitInterface;
use Nexus\Uom\Contracts\UnitSystemInterface;
use Nexus\Uom\Contracts\UomRepositoryInterface;

/**
 * Minimal in-memory UoM repository for Alpha + tests.
 *
 * This intentionally models a "generic" dimension so the quote-intelligence
 * normalization step can run without requiring full UoM seed data.
 */
final class InMemoryUomRepository implements UomRepositoryInterface
{
    /** @var array<string, UnitInterface> */
    private array $units;

    /** @var array<string, DimensionInterface> */
    private array $dimensions;

    /** @var array<string, ConversionRuleInterface> keyed by "from:to" */
    private array $conversions;

    public function __construct()
    {
        $dimension = new class implements DimensionInterface {
            public function getCode(): string { return 'generic'; }
            public function getName(): string { return 'Generic'; }
            public function getBaseUnit(): string { return 'EA'; }
            public function getDescription(): ?string { return 'Alpha-only generic unit dimension'; }
            public function allowsOffset(): bool { return false; }
        };

        $this->dimensions = [
            $dimension->getCode() => $dimension,
        ];

        $ea = $this->unit('EA', 'Each', 'EA', 'generic', true);
        $m = $this->unit('M', 'Meter', 'M', 'generic', false);

        $this->units = [
            $ea->getCode() => $ea,
            $m->getCode() => $m,
        ];

        // Alpha simplification: allow conversions between known units with 1:1 ratio.
        $this->conversions = [
            'EA:M' => $this->rule('EA', 'M', 1.0),
            'M:EA' => $this->rule('M', 'EA', 1.0),
        ];
    }

    public function findUnitByCode(string $code): ?UnitInterface
    {
        return $this->units[$code] ?? null;
    }

    public function findDimensionByCode(string $code): ?DimensionInterface
    {
        return $this->dimensions[$code] ?? null;
    }

    public function getUnitsByDimension(string $dimensionCode): array
    {
        return array_values(array_filter(
            $this->units,
            static fn (UnitInterface $u): bool => $u->getDimension() === $dimensionCode,
        ));
    }

    public function getUnitsBySystem(string $systemCode): array
    {
        return [];
    }

    public function findConversion(string $fromUnitCode, string $toUnitCode): ?ConversionRuleInterface
    {
        return $this->conversions["{$fromUnitCode}:{$toUnitCode}"] ?? null;
    }

    public function getConversionsFrom(string $fromUnitCode): array
    {
        $prefix = "{$fromUnitCode}:";

        return array_values(array_filter(
            $this->conversions,
            static fn (string $key): bool => str_starts_with($key, $prefix),
            ARRAY_FILTER_USE_KEY,
        ));
    }

    public function getConversionsByDimension(string $dimensionCode): array
    {
        if ($dimensionCode !== 'generic') {
            return [];
        }

        return array_values($this->conversions);
    }

    public function saveUnit(UnitInterface $unit): UnitInterface
    {
        $this->units[$unit->getCode()] = $unit;

        return $unit;
    }

    public function saveDimension(DimensionInterface $dimension): DimensionInterface
    {
        $this->dimensions[$dimension->getCode()] = $dimension;

        return $dimension;
    }

    public function saveConversion(ConversionRuleInterface $rule): ConversionRuleInterface
    {
        $this->conversions[$rule->getFromUnit() . ':' . $rule->getToUnit()] = $rule;

        return $rule;
    }

    public function ensureUniqueCode(string $code): bool
    {
        return !isset($this->units[$code]);
    }

    public function getAllDimensions(): array
    {
        return array_values($this->dimensions);
    }

    public function getAllUnitSystems(): array
    {
        return [];
    }

    private function unit(string $code, string $name, string $symbol, string $dimension, bool $isBase): UnitInterface
    {
        return new class($code, $name, $symbol, $dimension, $isBase) implements UnitInterface {
            public function __construct(
                private readonly string $code,
                private readonly string $name,
                private readonly string $symbol,
                private readonly string $dimension,
                private readonly bool $isBase,
            ) {}

            public function getCode(): string { return $this->code; }
            public function getName(): string { return $this->name; }
            public function getSymbol(): string { return $this->symbol; }
            public function getDimension(): string { return $this->dimension; }
            public function getSystem(): ?string { return null; }
            public function isBaseUnit(): bool { return $this->isBase; }
            public function isSystemUnit(): bool { return true; }
        };
    }

    private function rule(string $from, string $to, float $ratio): ConversionRuleInterface
    {
        return new class($from, $to, $ratio) implements ConversionRuleInterface {
            public function __construct(
                private readonly string $from,
                private readonly string $to,
                private readonly float $ratio,
            ) {}

            public function getFromUnit(): string { return $this->from; }
            public function getToUnit(): string { return $this->to; }
            public function getRatio(): float { return $this->ratio; }
            public function getOffset(): float { return 0.0; }
            public function hasOffset(): bool { return false; }
            public function isBidirectional(): bool { return true; }
        };
    }
}

