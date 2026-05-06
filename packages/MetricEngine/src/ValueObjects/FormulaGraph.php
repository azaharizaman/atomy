<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

final readonly class FormulaGraph
{
    /**
     * @param list<string> $orderedFormulaIds
     * @param array<string, list<string>> $dependencies
     */
    public function __construct(
        private array $orderedFormulaIds,
        private array $dependencies
    ) {}

    /** @return list<string> */
    public function orderedFormulaIds(): array
    {
        return $this->orderedFormulaIds;
    }

    /** @return list<string> */
    public function dependenciesFor(string $identifier): array
    {
        return $this->dependencies[$identifier] ?? [];
    }
}
