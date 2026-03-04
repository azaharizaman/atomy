<?php

declare(strict_types=1);

namespace Nexus\Budget\Listeners;

use Nexus\Budget\Services\BudgetRolloverHandler;
use Nexus\Budget\Services\BudgetForecastService;
use Nexus\Budget\Services\UtilizationAlertManager;
use Nexus\Budget\Contracts\PeriodClosedEventInterface;
use Nexus\Budget\Contracts\PeriodOpenedEventInterface;
use Psr\Log\LoggerInterface;

/**
 * Period Event Listener
 * 
 * Listens to Period package events to manage budget lifecycle.
 * - Period Closing: Trigger rollover processing
 * - Period Opened: Generate forecasts for new period budgets
 */
final readonly class PeriodEventListener
{
    public function __construct(
        private BudgetRolloverHandler $rolloverHandler,
        private BudgetForecastService $forecastService,
        private UtilizationAlertManager $alertManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle period closed event - process budget rollovers
     */
    public function onPeriodClosed(PeriodClosedEventInterface $event): void
    {
        try {
            $this->logger->info('Processing budget rollovers for closed period', [
                'period_id' => $event->getPeriodId(),
            ]);

            // Process rollovers based on budget policies
            $this->rolloverHandler->processRollover($event->getPeriodId());

            // Perform final utilization check
            $this->performFinalUtilizationCheck($event->getPeriodId());

            $this->logger->info('Budget rollover processing completed', [
                'period_id' => $event->getPeriodId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process budget rollovers', [
                'period_id' => $event->getPeriodId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle period opened event - generate forecasts
     */
    public function onPeriodOpened(PeriodOpenedEventInterface $event): void
    {
        try {
            $this->logger->info('Generating forecasts for new period budgets', [
                'period_id' => $event->getPeriodId(),
            ]);

            // Generate forecasts for all budgets in the new period
            $forecasts = $this->forecastService->generatePeriodForecasts($event->getPeriodId());

            $this->logger->info('Budget forecasts generated for new period', [
                'period_id' => $event->getPeriodId(),
                'forecast_count' => count($forecasts),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate period forecasts', [
                'period_id' => $event->getPeriodId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Perform final utilization check before period close
     */
    private function performFinalUtilizationCheck(string $periodId): void
    {
        try {
            $alerts = $this->alertManager->performPeriodicCheck($periodId);
            
            $this->logger->info('Final utilization check completed', [
                'period_id' => $periodId,
                'alerts_triggered' => count($alerts),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to perform final utilization check', [
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
