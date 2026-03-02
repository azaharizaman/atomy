<?php

declare(strict_types=1);

namespace App\Service\Settings\Adapters;

use Nexus\SettingsManagement\Contracts\FiscalPeriodProviderInterface;

final readonly class FiscalPeriodProvider implements FiscalPeriodProviderInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $mockPeriods;

    public function __construct()
    {
        // Mock data for fiscal periods
        $this->mockPeriods = [
            'tenant_1' => [
                'periods' => [
                    ['id' => 'P1', 'name' => 'January 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'status' => 'closed', 'locked' => true, 'adjusting' => false],
                    ['id' => 'P2', 'name' => 'February 2026', 'start_date' => '2026-02-01', 'end_date' => '2026-02-28', 'status' => 'open', 'locked' => false, 'adjusting' => false],
                    ['id' => 'P3', 'name' => 'March 2026', 'start_date' => '2026-03-01', 'end_date' => '2026-03-31', 'status' => 'future', 'locked' => false, 'adjusting' => false],
                ],
                'config' => [
                    'fiscal_year_start' => '2026-01-01',
                    'period_type' => 'monthly',
                ]
            ]
        ];
    }

    public function getPeriod(string $periodId, string $tenantId): ?array
    {
        $periods = $this->getAllPeriods($tenantId);
        foreach ($periods as $period) {
            if ($period['id'] === $periodId) {
                return $period;
            }
        }
        return null;
    }

    public function getAllPeriods(string $tenantId): array
    {
        return $this->mockPeriods[$tenantId]['periods'] ?? [];
    }

    public function getCurrentPeriod(string $tenantId): ?array
    {
        $periods = $this->getAllPeriods($tenantId);
        foreach ($periods as $period) {
            if ($period['status'] === 'open') {
                return $period;
            }
        }
        return null;
    }

    public function getPeriodByDate(\DateTimeInterface $date, string $tenantId): ?array
    {
        $periods = $this->getAllPeriods($tenantId);
        $dateStr = $date->format('Y-m-d');
        foreach ($periods as $period) {
            if ($dateStr >= $period['start_date'] && $dateStr <= $period['end_date']) {
                return $period;
            }
        }
        return null;
    }

    public function getCalendarConfig(string $tenantId): ?array
    {
        return $this->mockPeriods[$tenantId]['config'] ?? null;
    }

    public function isPeriodOpen(string $periodId, string $tenantId): bool
    {
        $period = $this->getPeriod($periodId, $tenantId);
        return ($period && $period['status'] === 'open');
    }

    public function isAdjustingPeriod(string $periodId, string $tenantId): bool
    {
        $period = $this->getPeriod($periodId, $tenantId);
        return ($period && ($period['adjusting'] ?? false));
    }

    public function isPeriodLocked(string $periodId, string $tenantId): bool
    {
        $period = $this->getPeriod($periodId, $tenantId);
        return ($period && ($period['locked'] ?? false));
    }
}
