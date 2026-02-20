<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\ReceivableDataProviderInterface;
use Nexus\Treasury\Contracts\TreasuryForecastInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Enums\ForecastScenario;
use Nexus\Treasury\Exceptions\InvalidForecastScenarioException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class TreasuryForecastService
{
    private const SCENARIO_ADJUSTMENTS = [
        ForecastScenario::OPTIMISTIC->value => 1.15,
        ForecastScenario::BASE->value => 1.0,
        ForecastScenario::PESSIMISTIC->value => 0.85,
    ];

    public function __construct(
        private TreasuryPolicyQueryInterface $policyQuery,
        private TreasuryPositionService $positionService,
        private ?PayableDataProviderInterface $payableProvider = null,
        private ?ReceivableDataProviderInterface $receivableProvider = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function generateForecast(
        string $tenantId,
        ForecastScenario $scenario,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): TreasuryForecastInterface {
        $this->validateScenario($scenario);

        $position = $this->positionService->calculatePosition($tenantId, null, $startDate);
        $openingBalance = $position->getAvailableCashBalance();
        $currency = $position->getCurrency();

        $adjustment = self::SCENARIO_ADJUSTMENTS[$scenario->value] ?? 1.0;

        $projectedInflows = $this->projectInflows($tenantId, $startDate, $endDate, $currency)
            ->multiply($adjustment);
        $projectedOutflows = $this->projectOutflows($tenantId, $startDate, $endDate, $currency);

        if ($scenario === ForecastScenario::PESSIMISTIC) {
            $projectedOutflows = $projectedOutflows->multiply(1.1);
        }

        $closingBalance = $openingBalance
            ->add($projectedInflows)
            ->subtract($projectedOutflows);

        $minimumBalance = $this->calculateMinimumBalance($tenantId, $startDate, $endDate, $currency);
        $maximumBalance = $this->calculateMaximumBalance($tenantId, $startDate, $endDate, $currency);

        $confidenceLevel = $this->calculateConfidenceLevel($scenario);
        $assumptions = $this->buildAssumptions($scenario, $adjustment);
        $riskFactors = $this->identifyRiskFactors($tenantId, $scenario);

        $this->logger->info('Treasury forecast generated', [
            'tenant_id' => $tenantId,
            'scenario' => $scenario->value,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'opening_balance' => $openingBalance->format(),
            'closing_balance' => $closingBalance->format(),
        ]);

        return new class(
            $this->generateId(),
            $tenantId,
            $scenario,
            $startDate,
            $endDate,
            $openingBalance,
            $projectedInflows,
            $projectedOutflows,
            $closingBalance,
            $minimumBalance,
            $maximumBalance,
            $confidenceLevel,
            $assumptions,
            $riskFactors,
            $currency
        ) implements TreasuryForecastInterface {
            private DateTimeImmutable $createdAt;

            public function __construct(
                private string $id,
                private string $tenantId,
                private ForecastScenario $scenario,
                private DateTimeImmutable $forecastStartDate,
                private DateTimeImmutable $forecastEndDate,
                private Money $openingBalance,
                private Money $projectedInflows,
                private Money $projectedOutflows,
                private Money $closingBalance,
                private Money $minimumBalance,
                private Money $maximumBalance,
                private float $confidenceLevel,
                private array $assumptions,
                private array $riskFactors,
                private string $currency
            ) {
                $this->createdAt = new DateTimeImmutable();
            }

            public function getId(): string { return $this->id; }
            public function getTenantId(): string { return $this->tenantId; }
            public function getScenario(): ForecastScenario { return $this->scenario; }
            public function getForecastStartDate(): DateTimeImmutable { return $this->forecastStartDate; }
            public function getForecastEndDate(): DateTimeImmutable { return $this->forecastEndDate; }
            public function getOpeningBalance(): Money { return $this->openingBalance; }
            public function getProjectedInflows(): Money { return $this->projectedInflows; }
            public function getProjectedOutflows(): Money { return $this->projectedOutflows; }
            public function getClosingBalance(): Money { return $this->closingBalance; }
            public function getMinimumBalance(): Money { return $this->minimumBalance; }
            public function getMaximumBalance(): Money { return $this->maximumBalance; }
            public function getConfidenceLevel(): float { return $this->confidenceLevel; }
            public function getAssumptions(): array { return $this->assumptions; }
            public function getRiskFactors(): array { return $this->riskFactors; }
            public function getCurrency(): string { return $this->currency; }
            public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
            public function getUpdatedAt(): DateTimeImmutable { return $this->createdAt; }
            public function getNetCashFlow(): Money { return $this->projectedInflows->subtract($this->projectedOutflows); }
        };
    }

    public function getScenarioComparison(
        string $tenantId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array {
        $scenarios = [
            ForecastScenario::OPTIMISTIC,
            ForecastScenario::BASE,
            ForecastScenario::PESSIMISTIC,
        ];

        $comparisons = [];

        foreach ($scenarios as $scenario) {
            $forecast = $this->generateForecast($tenantId, $scenario, $startDate, $endDate);

            $comparisons[$scenario->value] = [
                'opening_balance' => $forecast->getOpeningBalance()->getAmount(),
                'projected_inflows' => $forecast->getProjectedInflows()->getAmount(),
                'projected_outflows' => $forecast->getProjectedOutflows()->getAmount(),
                'closing_balance' => $forecast->getClosingBalance()->getAmount(),
                'net_cash_flow' => $forecast->getNetCashFlow()->getAmount(),
                'confidence_level' => $forecast->getConfidenceLevel(),
            ];
        }

        return $comparisons;
    }

    public function identifyCashGaps(
        string $tenantId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        Money $minimumRequired
    ): array {
        $forecast = $this->generateForecast($tenantId, ForecastScenario::BASE, $startDate, $endDate);

        $gaps = [];

        if ($forecast->getClosingBalance()->lessThan($minimumRequired)) {
            $gaps[] = [
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'projected_balance' => $forecast->getClosingBalance()->getAmount(),
                'minimum_required' => $minimumRequired->getAmount(),
                'shortfall' => $minimumRequired->subtract($forecast->getClosingBalance())->getAmount(),
                'severity' => 'warning',
            ];
        }

        return $gaps;
    }

    private function projectInflows(
        string $tenantId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $currency
    ): Money {
        if ($this->receivableProvider === null) {
            return Money::of(0, $currency);
        }

        $totalReceivables = $this->receivableProvider->getTotalReceivables(
            $tenantId,
            $startDate->format('Y-m-d')
        );

        $collectionPeriod = $this->receivableProvider->getAverageCollectionPeriod($tenantId);
        if ($collectionPeriod <= 0) {
            $collectionPeriod = 30;
        }

        $days = (int) $startDate->diff($endDate)->days;
        $collectionCycles = $days / $collectionPeriod;

        return Money::of($totalReceivables * $collectionCycles, $currency);
    }

    private function projectOutflows(
        string $tenantId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $currency
    ): Money {
        if ($this->payableProvider === null) {
            return Money::of(0, $currency);
        }

        $totalPayables = $this->payableProvider->getTotalPayables(
            $tenantId,
            $startDate->format('Y-m-d')
        );

        $paymentPeriod = $this->payableProvider->getAveragePaymentPeriod($tenantId);
        if ($paymentPeriod <= 0) {
            $paymentPeriod = 30;
        }

        $days = (int) $startDate->diff($endDate)->days;
        $paymentCycles = $days / $paymentPeriod;

        return Money::of($totalPayables * $paymentCycles, $currency);
    }

    private function calculateMinimumBalance(
        string $tenantId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $currency
    ): Money {
        $policy = $this->policyQuery->findEffectiveForDate($tenantId, $startDate);

        if ($policy !== null) {
            return $policy->getMinimumCashBalance();
        }

        return Money::of(0, $currency);
    }

    private function calculateMaximumBalance(
        string $tenantId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $currency
    ): Money {
        return Money::of('999999999999', $currency);
    }

    private function calculateConfidenceLevel(ForecastScenario $scenario): float
    {
        return match ($scenario) {
            ForecastScenario::OPTIMISTIC => 0.60,
            ForecastScenario::BASE => 0.80,
            ForecastScenario::PESSIMISTIC => 0.75,
        };
    }

    private function buildAssumptions(ForecastScenario $scenario, float $adjustment): array
    {
        return [
            'scenario' => $scenario->value,
            'inflow_adjustment_factor' => $adjustment,
            'outflow_adjustment_factor' => $scenario === ForecastScenario::PESSIMISTIC ? 1.1 : 1.0,
            'collection_period_assumed' => 30,
            'payment_period_assumed' => 30,
            'generated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    private function identifyRiskFactors(string $tenantId, ForecastScenario $scenario): array
    {
        $risks = [];

        if ($scenario === ForecastScenario::PESSIMISTIC) {
            $risks[] = [
                'type' => 'liquidity',
                'description' => 'Reduced expected cash inflows',
                'impact' => 'medium',
            ];
            $risks[] = [
                'type' => 'collection',
                'description' => 'Extended customer payment cycles',
                'impact' => 'medium',
            ];
        }

        return $risks;
    }

    private function validateScenario(ForecastScenario $scenario): void
    {
        if (!isset(self::SCENARIO_ADJUSTMENTS[$scenario->value])) {
            throw InvalidForecastScenarioException::forScenario($scenario->value);
        }
    }

    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-FCT-' . $uuid;
    }
}
