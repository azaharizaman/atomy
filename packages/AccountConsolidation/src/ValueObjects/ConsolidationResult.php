<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\ValueObjects;

/**
 * Represents the result of a consolidation.
 */
final readonly class ConsolidationResult
{
    /**
     * @param array<string, mixed> $consolidatedBalances
     * @param array<EliminationEntry> $eliminations
     * @param array<TranslationAdjustment> $translationAdjustments
     * @param array<string, mixed> $nciData
     */
    public function __construct(
        private array $consolidatedBalances,
        private array $eliminations,
        private array $translationAdjustments,
        private array $nciData,
        private \DateTimeImmutable $asOfDate,
    ) {}

    public function getConsolidatedBalances(): array
    {
        return $this->consolidatedBalances;
    }

    /**
     * @return array<EliminationEntry>
     */
    public function getEliminations(): array
    {
        return $this->eliminations;
    }

    /**
     * @return array<TranslationAdjustment>
     */
    public function getTranslationAdjustments(): array
    {
        return $this->translationAdjustments;
    }

    public function getNciData(): array
    {
        return $this->nciData;
    }

    public function getAsOfDate(): \DateTimeImmutable
    {
        return $this->asOfDate;
    }
}
