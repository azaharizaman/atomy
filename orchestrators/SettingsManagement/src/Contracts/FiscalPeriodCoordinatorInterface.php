<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

use Nexus\SettingsManagement\DTOs\FiscalPeriod\CalendarConfigRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\CalendarConfigResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodGenerationRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodGenerationResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodValidationRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodValidationResult;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodCloseRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodCloseResult;

/**
 * Coordinator interface for managing fiscal periods.
 */
interface FiscalPeriodCoordinatorInterface extends SettingsCoordinatorInterfaceBase
{
    /**
     * Configure fiscal calendar for a tenant.
     */
    public function configureCalendar(CalendarConfigRequest $request): CalendarConfigResult;

    /**
     * Generate fiscal periods based on calendar configuration.
     */
    public function generatePeriods(PeriodGenerationRequest $request): PeriodGenerationResult;

    /**
     * Validate period status for specific operation type.
     */
    public function validatePeriod(PeriodValidationRequest $request): PeriodValidationResult;

    /**
     * Close a fiscal period.
     */
    public function closePeriod(PeriodCloseRequest $request): PeriodCloseResult;

    /**
     * Get all fiscal periods for a tenant.
     */
    public function getAllPeriods(string $tenantId): array;

    /**
     * Get current open period for a tenant.
     */
    public function getCurrentPeriod(string $tenantId): ?array;

    /**
     * Get fiscal calendar configuration for a tenant.
     */
    public function getCalendarConfig(string $tenantId): ?array;
}
