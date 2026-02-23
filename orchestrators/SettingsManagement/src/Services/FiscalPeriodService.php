<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Services;

use Nexus\SettingsManagement\Contracts\FiscalPeriodProviderInterface;
use Nexus\SettingsManagement\Contracts\FiscalPeriodServiceInterface;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\CalendarConfigRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\CalendarConfigResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodCloseRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodCloseResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodGenerationRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodGenerationResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodValidationRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Orchestration service for fiscal period management.
 * Handles period lifecycle with validation and audit logging.
 */
final class FiscalPeriodService implements FiscalPeriodServiceInterface
{
    public function __construct(
        private readonly FiscalPeriodProviderInterface $periodProvider,
        private readonly LoggerInterface $logger,
    ) {}

    public function configureCalendar(CalendarConfigRequest $request): CalendarConfigResult
    {
        $this->logger->info('Configuring fiscal calendar', [
            'tenant_id' => $request->tenantId,
            'period_type' => $request->periodType->value,
            'fiscal_year_start' => $request->fiscalYearStart->format('Y-m-d'),
        ]);

        try {
            // In production, this would call the Period package
            $calendarId = 'cal_' . uniqid();

            $this->logger->info('Fiscal calendar configured', [
                'calendar_id' => $calendarId,
            ]);

            return CalendarConfigResult::success($calendarId);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to configure fiscal calendar', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return CalendarConfigResult::failure($e->getMessage());
        }
    }

    public function generatePeriods(PeriodGenerationRequest $request): PeriodGenerationResult
    {
        $this->logger->info('Generating fiscal periods', [
            'calendar_id' => $request->calendarId,
            'fiscal_year' => $request->fiscalYear,
            'number_of_periods' => $request->numberOfPeriods,
        ]);

        try {
            // In production, this would generate periods based on calendar config
            $periodIds = [];
            for ($i = 1; $i <= $request->numberOfPeriods; $i++) {
                $periodIds[] = "period_{$request->fiscalYear}_{$i}";
            }

            $this->logger->info('Fiscal periods generated', [
                'count' => count($periodIds),
            ]);

            return PeriodGenerationResult::success($periodIds);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate fiscal periods', [
                'calendar_id' => $request->calendarId,
                'error' => $e->getMessage(),
            ]);

            return PeriodGenerationResult::failure($e->getMessage());
        }
    }

    public function validatePeriod(PeriodValidationRequest $request): PeriodValidationResult
    {
        $period = $this->periodProvider->getPeriod($request->periodId, $request->tenantId);

        if (!$period) {
            return PeriodValidationResult::invalid("Period '{$request->periodId}' not found");
        }

        $isOpen = $this->periodProvider->isPeriodOpen($request->periodId, $request->tenantId);
        $allowsAdjustment = $this->periodProvider->isAdjustingPeriod($request->periodId, $request->tenantId);
        $isLocked = $this->periodProvider->isPeriodLocked($request->periodId, $request->tenantId);

        return PeriodValidationResult::valid($isOpen, $allowsAdjustment, $isLocked);
    }

    public function closePeriod(PeriodCloseRequest $request): PeriodCloseResult
    {
        $this->logger->info('Closing fiscal period', [
            'period_id' => $request->periodId,
            'tenant_id' => $request->tenantId,
            'generate_closing_entries' => $request->generateClosingEntries,
        ]);

        try {
            // Validate period can be closed
            $period = $this->periodProvider->getPeriod($request->periodId, $request->tenantId);
            
            if (!$period) {
                return PeriodCloseResult::failure("Period '{$request->periodId}' not found");
            }

            if ($this->periodProvider->isPeriodLocked($request->periodId, $request->tenantId)) {
                return PeriodCloseResult::failure("Period '{$request->periodId}' is locked");
            }

            // Generate closing entries if requested
            $closingEntries = [];
            if ($request->generateClosingEntries) {
                // In production, this would generate closing entries
                $closingEntries = ['entry_1', 'entry_2'];
            }

            $this->logger->info('Fiscal period closed', [
                'period_id' => $request->periodId,
            ]);

            return PeriodCloseResult::success($request->periodId, $closingEntries);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to close fiscal period', [
                'period_id' => $request->periodId,
                'error' => $e->getMessage(),
            ]);

            return PeriodCloseResult::failure($e->getMessage());
        }
    }
}
