<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Treasury\Contracts\Integration\InventoryDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\ReceivableDataProviderInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Contracts\WorkingCapitalOptimizerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class WorkingCapitalOptimizer
{
    public function __construct(
        private TreasuryPolicyQueryInterface $policyQuery,
        private TreasuryPositionService $positionService,
        private ?PayableDataProviderInterface $payableProvider = null,
        private ?ReceivableDataProviderInterface $receivableProvider = null,
        private ?InventoryDataProviderInterface $inventoryProvider = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function calculateMetrics(
        string $tenantId,
        ?DateTimeImmutable $asOfDate = null
    ): WorkingCapitalOptimizerInterface {
        $date = $asOfDate ?? new DateTimeImmutable();

        $dso = $this->getDaysSalesOutstanding($tenantId);
        $dpo = $this->getDaysPayableOutstanding($tenantId);
        $dio = $this->getDaysInventoryOutstanding($tenantId);
        $ccc = $dso + $dio - $dpo;

        $workingCapital = $this->calculateWorkingCapital($tenantId, $date);
        $workingCapitalRatio = $this->calculateWorkingCapitalRatio($tenantId);

        $opportunities = $this->identifyOptimizationOpportunities($tenantId, $dso, $dpo, $dio, $ccc);
        $recommendations = $this->generateRecommendations($opportunities);

        $this->logger->info('Working capital metrics calculated', [
            'tenant_id' => $tenantId,
            'dso' => $dso,
            'dpo' => $dpo,
            'dio' => $dio,
            'ccc' => $ccc,
        ]);

        return new class(
            $this->generateId(),
            $tenantId,
            $date,
            $dso,
            $dpo,
            $dio,
            $ccc,
            $workingCapital,
            $workingCapitalRatio,
            $opportunities,
            $recommendations
        ) implements WorkingCapitalOptimizerInterface {
            public function __construct(
                private string $id,
                private string $tenantId,
                private DateTimeImmutable $calculationDate,
                private float $daysSalesOutstanding,
                private float $daysPayableOutstanding,
                private float $daysInventoryOutstanding,
                private float $cashConversionCycle,
                private float $workingCapital,
                private float $workingCapitalRatio,
                private array $optimizationOpportunities,
                private array $recommendations
            ) {}

            public function getId(): string { return $this->id; }
            public function getTenantId(): string { return $this->tenantId; }
            public function getCalculationDate(): DateTimeImmutable { return $this->calculationDate; }
            public function getDaysSalesOutstanding(): float { return $this->daysSalesOutstanding; }
            public function getDaysPayableOutstanding(): float { return $this->daysPayableOutstanding; }
            public function getDaysInventoryOutstanding(): float { return $this->daysInventoryOutstanding; }
            public function getCashConversionCycle(): float { return $this->cashConversionCycle; }
            public function getWorkingCapital(): float { return $this->workingCapital; }
            public function getWorkingCapitalRatio(): float { return $this->workingCapitalRatio; }
            public function getOptimizationOpportunities(): array { return $this->optimizationOpportunities; }
            public function getRecommendations(): array { return $this->recommendations; }
            public function getCurrency(): string { return 'USD'; }
            public function getCreatedAt(): DateTimeImmutable { return $this->calculationDate; }
            public function getUpdatedAt(): DateTimeImmutable { return $this->calculationDate; }
            public function hasNegativeCycle(): bool { return $this->cashConversionCycle < 0; }
        };
    }

    public function getCashConversionCycleAnalysis(string $tenantId, ?DateTimeImmutable $asOfDate = null): array
    {
        $metrics = $this->calculateMetrics($tenantId, $asOfDate);

        return [
            'days_sales_outstanding' => [
                'value' => $metrics->getDaysSalesOutstanding(),
                'benchmark' => 30,
                'variance' => $metrics->getDaysSalesOutstanding() - 30,
                'status' => $metrics->getDaysSalesOutstanding() <= 30 ? 'good' : 'needs_improvement',
            ],
            'days_payable_outstanding' => [
                'value' => $metrics->getDaysPayableOutstanding(),
                'benchmark' => 45,
                'variance' => $metrics->getDaysPayableOutstanding() - 45,
                'status' => $metrics->getDaysPayableOutstanding() >= 45 ? 'good' : 'needs_improvement',
            ],
            'days_inventory_outstanding' => [
                'value' => $metrics->getDaysInventoryOutstanding(),
                'benchmark' => 30,
                'variance' => $metrics->getDaysInventoryOutstanding() - 30,
                'status' => $metrics->getDaysInventoryOutstanding() <= 30 ? 'good' : 'needs_improvement',
            ],
            'cash_conversion_cycle' => [
                'value' => $metrics->getCashConversionCycle(),
                'benchmark' => 15,
                'variance' => $metrics->getCashConversionCycle() - 15,
                'status' => $metrics->hasNegativeCycle() ? 'excellent' : ($metrics->getCashConversionCycle() <= 15 ? 'good' : 'needs_improvement'),
            ],
        ];
    }

    private function getDaysSalesOutstanding(string $tenantId): float
    {
        if ($this->receivableProvider === null) {
            return 0.0;
        }

        return $this->receivableProvider->getDaysSalesOutstanding($tenantId);
    }

    private function getDaysPayableOutstanding(string $tenantId): float
    {
        if ($this->payableProvider === null) {
            return 0.0;
        }

        return $this->payableProvider->getDaysPayableOutstanding($tenantId);
    }

    private function getDaysInventoryOutstanding(string $tenantId): float
    {
        if ($this->inventoryProvider === null) {
            return 0.0;
        }

        return $this->inventoryProvider->getDaysInventoryOutstanding($tenantId);
    }

    private function calculateWorkingCapital(string $tenantId, DateTimeImmutable $date): float
    {
        return 0.0;
    }

    private function calculateWorkingCapitalRatio(string $tenantId): float
    {
        return 1.5;
    }

    private function identifyOptimizationOpportunities(
        string $tenantId,
        float $dso,
        float $dpo,
        float $dio,
        float $ccc
    ): array {
        $opportunities = [];

        if ($dso > 45) {
            $opportunities[] = [
                'area' => 'receivables',
                'current_value' => $dso,
                'target_value' => 30,
                'potential_savings' => ($dso - 30) * 100,
                'priority' => 'high',
                'description' => 'Reduce collection period to improve cash flow',
            ];
        }

        if ($dpo < 30) {
            $opportunities[] = [
                'area' => 'payables',
                'current_value' => $dpo,
                'target_value' => 45,
                'potential_savings' => (45 - $dpo) * 50,
                'priority' => 'medium',
                'description' => 'Extend payment terms with suppliers',
            ];
        }

        if ($dio > 45) {
            $opportunities[] = [
                'area' => 'inventory',
                'current_value' => $dio,
                'target_value' => 30,
                'potential_savings' => ($dio - 30) * 75,
                'priority' => 'high',
                'description' => 'Optimize inventory levels',
            ];
        }

        return $opportunities;
    }

    private function generateRecommendations(array $opportunities): array
    {
        $recommendations = [];

        foreach ($opportunities as $opportunity) {
            $recommendations[] = [
                'action' => $this->getActionForArea($opportunity['area']),
                'impact' => $opportunity['priority'],
                'description' => $opportunity['description'],
                'expected_improvement' => $opportunity['target_value'] - $opportunity['current_value'],
            ];
        }

        return $recommendations;
    }

    private function getActionForArea(string $area): string
    {
        return match ($area) {
            'receivables' => 'Accelerate collections',
            'payables' => 'Negotiate extended payment terms',
            'inventory' => 'Implement just-in-time inventory',
            default => 'Optimize processes',
        };
    }

    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-WCO-' . $uuid;
    }
}
