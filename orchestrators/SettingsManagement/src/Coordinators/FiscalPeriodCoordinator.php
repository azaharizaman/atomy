<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Coordinators;

use Nexus\SettingsManagement\Contracts\FiscalPeriodCoordinatorInterface;
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
use Nexus\SettingsManagement\Rules\FiscalPeriodLockedRule;
use Nexus\SettingsManagement\Rules\FiscalPeriodOverlapRule;

/**
 * Coordinator for managing fiscal periods.
 * Orchestrates validation rules, data providers, and services.
 */
final class FiscalPeriodCoordinator implements FiscalPeriodCoordinatorInterface
{
    public function __construct(
        private readonly FiscalPeriodProviderInterface $periodProvider,
        private readonly FiscalPeriodServiceInterface $periodService,
    ) {}

    public function getName(): string
    {
        return 'FiscalPeriodCoordinator';
    }

    public function hasRequiredData(string $tenantId): bool
    {
        $calendar = $this->periodProvider->getCalendarConfig($tenantId);
        return $calendar !== null;
    }

    public function configureCalendar(CalendarConfigRequest $request): CalendarConfigResult
    {
        // Check if calendar already exists
        $existing = $this->periodProvider->getCalendarConfig($request->tenantId);
        if ($existing) {
            return CalendarConfigResult::failure('Fiscal calendar already configured for this tenant');
        }

        return $this->periodService->configureCalendar($request);
    }

    public function generatePeriods(PeriodGenerationRequest $request): PeriodGenerationResult
    {
        // Validate calendar exists
        $calendar = $this->periodProvider->getCalendarConfig($request->tenantId);
        if (!$calendar) {
            return PeriodGenerationResult::failure('Fiscal calendar not configured');
        }

        // Check for overlapping periods
        $existingPeriods = $this->periodProvider->getAllPeriods($request->tenantId);
        
        // In production, this would validate against period generation rules

        return $this->periodService->generatePeriods($request);
    }

    public function validatePeriod(PeriodValidationRequest $request): PeriodValidationResult
    {
        return $this->periodService->validatePeriod($request);
    }

    public function closePeriod(PeriodCloseRequest $request): PeriodCloseResult
    {
        // Validate period can be closed
        $period = $this->periodProvider->getPeriod($request->periodId, $request->tenantId);
        
        if (!$period) {
            return PeriodCloseResult::failure("Period '{$request->periodId}' not found");
        }

        // Run validation rules
        $lockedRule = new FiscalPeriodLockedRule();
        $validationContext = [
            'periodId' => $request->periodId,
            'operationType' => $request->generateClosingEntries ? 'closing' : 'transaction',
            'period' => $period,
        ];
        
        $ruleResult = $lockedRule->evaluate($validationContext);
        if (!$ruleResult->passed) {
            return PeriodCloseResult::failure($ruleResult->error ?? 'Period is locked');
        }

        return $this->periodService->closePeriod($request);
    }

    public function getAllPeriods(string $tenantId): array
    {
        return $this->periodProvider->getAllPeriods($tenantId);
    }

    public function getCurrentPeriod(string $tenantId): ?array
    {
        return $this->periodProvider->getCurrentPeriod($tenantId);
    }

    public function getCalendarConfig(string $tenantId): ?array
    {
        return $this->periodProvider->getCalendarConfig($tenantId);
    }
}
