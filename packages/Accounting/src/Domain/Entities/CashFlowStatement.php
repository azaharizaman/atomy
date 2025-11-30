<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Entities;

use Nexus\Accounting\Domain\Contracts\CashFlowStatementInterface;
use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Domain\ValueObjects\StatementSection;
use Nexus\Accounting\Domain\Enums\StatementType;
use Nexus\Accounting\Domain\Enums\CashFlowMethod;

/**
 * Cash Flow Statement entity implementation.
 */
final readonly class CashFlowStatement implements CashFlowStatementInterface
{
    /**
     * @param StatementSection[] $sections
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $entityId,
        private ReportingPeriod $period,
        private CashFlowMethod $method,
        private array $sections,
        private float $beginningCash,
        private float $endingCash,
        private array $metadata = [],
        private bool $locked = false
    ) {}

    public function getType(): StatementType
    {
        return StatementType::CASH_FLOW;
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
        return $this->getNetCashChange();
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
            'method' => $this->method->value,
            'sections' => array_map(fn($s) => $s->toArray(), $this->sections),
            'cash_flows' => [
                'operating' => $this->getCashFromOperations(),
                'investing' => $this->getCashFromInvesting(),
                'financing' => $this->getCashFromFinancing(),
                'net_change' => $this->getNetCashChange(),
            ],
            'cash_balances' => [
                'beginning' => $this->beginningCash,
                'ending' => $this->endingCash,
            ],
            'reconciled' => $this->verifyCashReconciliation(),
            'metadata' => $this->metadata,
            'locked' => $this->locked,
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getMethod(): CashFlowMethod
    {
        return $this->method;
    }

    public function getCashFromOperations(): float
    {
        $operatingSection = $this->getSection('OPERATING');
        return $operatingSection?->getTotal() ?? 0.0;
    }

    public function getCashFromInvesting(): float
    {
        $investingSection = $this->getSection('INVESTING');
        return $investingSection?->getTotal() ?? 0.0;
    }

    public function getCashFromFinancing(): float
    {
        $financingSection = $this->getSection('FINANCING');
        return $financingSection?->getTotal() ?? 0.0;
    }

    public function getNetCashChange(): float
    {
        return $this->getCashFromOperations()
            + $this->getCashFromInvesting()
            + $this->getCashFromFinancing();
    }

    public function getBeginningCash(): float
    {
        return $this->beginningCash;
    }

    public function getEndingCash(): float
    {
        return $this->endingCash;
    }

    public function verifyCashReconciliation(): bool
    {
        $calculated = $this->beginningCash + $this->getNetCashChange();
        
        // Allow for small rounding differences (0.01)
        return abs($calculated - $this->endingCash) < 0.01;
    }
}
