<?php

declare(strict_types=1);

namespace Nexus\Budget\Services;

use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetAnalyticsRepositoryInterface;
use Nexus\Budget\Contracts\BudgetForecastInterface;
use Nexus\Budget\Events\BudgetForecastGeneratedEvent;
use Nexus\Budget\ValueObjects\BudgetForecast;
use Nexus\Intelligence\Contracts\PredictionServiceInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Budget Forecast Service
 * 
 * Generates AI-powered forecasts for budget utilization and overrun probability.
 */
final readonly class BudgetForecastService implements BudgetForecastInterface
{
    public function __construct(
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetAnalyticsRepositoryInterface $analyticsRepository,
        private PredictionServiceInterface $predictionService,
        private PeriodManagerInterface $periodManager,
        private SettingsManagerInterface $settings,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    /**
     * Generate forecast for a specific budget
     */
    public function generateForecast(string $budgetId): BudgetForecast
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            throw new \InvalidArgumentException("Budget not found: {$budgetId}");
        }

        // Get prediction from Intelligence package
        $prediction = $this->predictionService->predict(
            $budget,
            'budget_overrun_probability'
        );

        // Calculate projected spending
        $projectedSpending = $this->calculateProjectedSpending($budget);
        
        // Calculate confidence intervals
        $lowerBound = $projectedSpending * 0.85; // 15% variance
        $upperBound = $projectedSpending * 1.15;

        $forecast = new BudgetForecast(
            budgetId: $budgetId,
            periodId: $budget->getPeriodId(),
            projectedSpending: $projectedSpending,
            projectedVariance: $projectedSpending - $budget->getAllocatedAmount()->getAmount(),
            overrunProbability: $prediction->getProbability(),
            confidenceInterval: [
                'lower' => $lowerBound,
                'upper' => $upperBound,
            ],
            certaintyScore: $prediction->getConfidence(),
            generatedAt: new \DateTimeImmutable()
        );

        // Publish event
        $this->eventDispatcher->dispatch(new BudgetForecastGeneratedEvent(
            budgetId: $budgetId,
            periodId: $budget->getPeriodId(),
            projectedSpending: $projectedSpending,
            overrunProbability: $prediction->getProbability(),
            certaintyScore: $prediction->getConfidence()
        ));

        $this->logger->info('Budget forecast generated', [
            'budget_id' => $budgetId,
            'projected_spending' => $projectedSpending,
            'overrun_probability' => $prediction->getProbability(),
        ]);

        return $forecast;
    }

    /**
     * Generate forecasts for all active budgets in a period
     */
    public function generatePeriodForecasts(string $periodId): array
    {
        $budgets = $this->budgetRepository->findByPeriod($periodId);
        $forecasts = [];

        foreach ($budgets as $budget) {
            try {
                $forecasts[] = $this->generateForecast($budget->getId());
            } catch (\Exception $e) {
                $this->logger->error('Forecast generation failed', [
                    'budget_id' => $budget->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $forecasts;
    }

    /**
     * Calculate projected spending based on current burn rate
     */
    private function calculateProjectedSpending(object $budget): float
    {
        $period = $this->periodManager->findById($budget->getPeriodId());
        if (!$period) {
            return $budget->getActualAmount()->getAmount();
        }

        $start = $period->getStartDate();
        $end = $period->getEndDate();
        $now = new \DateTimeImmutable();

        if ($now < $start) {
            return 0.0; // Period hasn't started
        }

        if ($now > $end) {
            return $budget->getActualAmount()->getAmount(); // Period closed
        }

        $totalDays = $start->diff($end)->days;
        $elapsedDays = $start->diff($now)->days;

        if ($elapsedDays == 0) {
            return $budget->getActualAmount()->getAmount();
        }

        // Linear projection
        $currentSpending = $budget->getActualAmount()->getAmount();
        $dailyBurnRate = $currentSpending / $elapsedDays;
        
        // Apply seasonality factor
        $seasonalityFactor = $this->getSeasonalityFactor($budget);
        
        $projectedSpending = $currentSpending + ($dailyBurnRate * ($totalDays - $elapsedDays) * $seasonalityFactor);

        return $projectedSpending;
    }

    /**
     * Get seasonality adjustment factor
     */
    private function getSeasonalityFactor(object $budget): float
    {
        $departmentId = $budget->getDepartmentId();
        if (!$departmentId) {
            return 1.0;
        }

        try {
            return $this->analyticsRepository->getSeasonalityFactor(
                $departmentId,
                $budget->getPeriodId()
            );
        } catch (\Exception $e) {
            return 1.0; // Default to no adjustment
        }
    }
}
