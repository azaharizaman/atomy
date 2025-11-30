<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Entities;

use Nexus\Accounting\Domain\Contracts\BalanceSheetInterface;
use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Domain\ValueObjects\StatementSection;
use Nexus\Accounting\Domain\Enums\StatementType;

/**
 * Balance Sheet entity implementation.
 */
final readonly class BalanceSheet implements BalanceSheetInterface
{
    /**
     * @param StatementSection[] $sections
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $entityId,
        private ReportingPeriod $period,
        private array $sections,
        private array $metadata = [],
        private bool $locked = false
    ) {}

    public function getType(): StatementType
    {
        return StatementType::BALANCE_SHEET;
    }

    public function getReportingPeriod(): ReportingPeriod
    {
        return $this->period;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function getSection(string $name): ?StatementSection
    {
        foreach ($this->sections as $section) {
            if ($section->getCode() === $name || $section->getName() === $name) {
                return $section;
            }
        }
        return null;
    }

    public function getGrandTotal(): float
    {
        return $this->getTotalAssets();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType()->value,
            'entity_id' => $this->entityId,
            'period' => $this->period->toArray(),
            'sections' => array_map(fn($s) => $s->toArray(), $this->sections),
            'totals' => [
                'assets' => $this->getTotalAssets(),
                'current_assets' => $this->getTotalCurrentAssets(),
                'non_current_assets' => $this->getTotalNonCurrentAssets(),
                'liabilities' => $this->getTotalLiabilities(),
                'current_liabilities' => $this->getTotalCurrentLiabilities(),
                'non_current_liabilities' => $this->getTotalNonCurrentLiabilities(),
                'equity' => $this->getTotalEquity(),
                'working_capital' => $this->getWorkingCapital(),
            ],
            'balanced' => $this->verifyBalance(),
            'metadata' => $this->metadata,
            'locked' => $this->locked,
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getTotalAssets(): float
    {
        $assetSection = $this->getSection('ASSETS');
        return $assetSection?->getTotal() ?? 0.0;
    }

    public function getTotalCurrentAssets(): float
    {
        $assetSection = $this->getSection('ASSETS');
        if (!$assetSection) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($assetSection->getLineItems() as $item) {
            $metadata = $item->getMetadata();
            if (isset($metadata['is_current']) && $metadata['is_current']) {
                $total += $item->getAmount();
            }
        }
        return $total;
    }

    public function getTotalNonCurrentAssets(): float
    {
        return $this->getTotalAssets() - $this->getTotalCurrentAssets();
    }

    public function getTotalLiabilities(): float
    {
        $liabilitySection = $this->getSection('LIABILITIES');
        return $liabilitySection?->getTotal() ?? 0.0;
    }

    public function getTotalCurrentLiabilities(): float
    {
        $liabilitySection = $this->getSection('LIABILITIES');
        if (!$liabilitySection) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($liabilitySection->getLineItems() as $item) {
            $metadata = $item->getMetadata();
            if (isset($metadata['is_current']) && $metadata['is_current']) {
                $total += $item->getAmount();
            }
        }
        return $total;
    }

    public function getTotalNonCurrentLiabilities(): float
    {
        return $this->getTotalLiabilities() - $this->getTotalCurrentLiabilities();
    }

    public function getTotalEquity(): float
    {
        $equitySection = $this->getSection('EQUITY');
        return $equitySection?->getTotal() ?? 0.0;
    }

    public function verifyBalance(): bool
    {
        $assets = $this->getTotalAssets();
        $liabilitiesAndEquity = $this->getTotalLiabilities() + $this->getTotalEquity();
        
        // Allow for small rounding differences (0.01)
        return abs($assets - $liabilitiesAndEquity) < 0.01;
    }

    public function getWorkingCapital(): float
    {
        return $this->getTotalCurrentAssets() - $this->getTotalCurrentLiabilities();
    }
}
