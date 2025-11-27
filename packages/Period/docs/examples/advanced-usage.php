<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Nexus Period Package
 *
 * This example demonstrates:
 * 1. Period lifecycle management (open, close, lock)
 * 2. Month-end close workflows
 * 3. Cross-package integration patterns
 * 4. Audit trail integration
 * 5. Multi-period type coordination
 *
 * @package Nexus\Period
 * @see docs/integration-guide.md for complete setup
 */

use DateTimeImmutable;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Contracts\PeriodRepositoryInterface;
use Nexus\Period\Contracts\AuditLoggerInterface;
use Nexus\Period\Contracts\AuthorizationInterface;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\PeriodNotFoundException;
use Nexus\Period\Exceptions\InvalidPeriodStatusException;
use Nexus\Period\Exceptions\PeriodReopeningUnauthorizedException;

// ============================================
// Example 1: Month-End Close Workflow
// ============================================

/**
 * Comprehensive month-end close process
 *
 * This workflow coordinates period closing across multiple
 * period types and integrates with other Nexus packages.
 */
final readonly class MonthEndCloseService
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        // These would be injected from other Nexus packages
        // private ReconciliationServiceInterface $reconciliation,
        // private GeneralLedgerManagerInterface $glManager,
        // private EventDispatcherInterface $events
    ) {}

    /**
     * Perform complete month-end close
     *
     * @throws \DomainException If prerequisites not met
     * @throws InvalidPeriodStatusException If period cannot be closed
     */
    public function performClose(
        string $periodId,
        string $userId,
        string $reason = 'Month-end close completed'
    ): void {
        // Step 1: Get the period
        $period = $this->periodManager->findById($periodId);

        // Step 2: Validate prerequisites
        $this->validatePrerequisites($period);

        // Step 3: Close the accounting period
        $this->periodManager->closePeriod(
            periodId: $periodId,
            reason: $reason,
            userId: $userId
        );

        // Step 4: Create next period (if not exists)
        $this->ensureNextPeriodExists($period);

        // Step 5: Open the next period
        $this->openNextPeriod($period);

        // Step 6: Dispatch events for downstream systems
        // $this->events->dispatch(new PeriodClosedEvent($period, $userId));
    }

    private function validatePrerequisites(PeriodInterface $period): void
    {
        // Ensure period is currently open
        if ($period->getStatus() !== PeriodStatus::Open) {
            throw new \DomainException(
                sprintf(
                    'Cannot close period %s: current status is %s',
                    $period->getName(),
                    $period->getStatus()->label()
                )
            );
        }

        // Example: Check bank reconciliation is complete
        // if (!$this->reconciliation->isComplete($period)) {
        //     throw new \DomainException('Bank reconciliation must be completed');
        // }

        // Example: Check trial balance
        // if (!$this->glManager->isTrialBalanceBalanced($period)) {
        //     throw new \DomainException('Trial balance is out of balance');
        // }
    }

    private function ensureNextPeriodExists(PeriodInterface $currentPeriod): void
    {
        $nextStartDate = $currentPeriod->getEndDate()->modify('+1 day');

        $nextPeriod = $this->periodManager->findPeriodByDate(
            $nextStartDate,
            $currentPeriod->getType()
        );

        if ($nextPeriod === null) {
            // Create next period if it doesn't exist
            // This would be done through repository or setup service
            throw new \DomainException(
                sprintf(
                    'Next period starting %s does not exist. Please create it first.',
                    $nextStartDate->format('Y-m-d')
                )
            );
        }
    }

    private function openNextPeriod(PeriodInterface $currentPeriod): void
    {
        $nextStartDate = $currentPeriod->getEndDate()->modify('+1 day');

        $nextPeriod = $this->periodManager->findPeriodByDate(
            $nextStartDate,
            $currentPeriod->getType()
        );

        if ($nextPeriod !== null && $nextPeriod->getStatus() === PeriodStatus::Pending) {
            $this->periodManager->openPeriod($nextPeriod->getId());
        }
    }
}

// ============================================
// Example 2: Year-End Lock Workflow
// ============================================

/**
 * Year-end period locking process
 *
 * Locked periods are permanently sealed and cannot be reopened.
 * This is typically done after external audit is complete.
 */
final readonly class YearEndLockService
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        private AuditLoggerInterface $auditLogger
    ) {}

    /**
     * Lock all periods for a fiscal year
     *
     * @param string $fiscalYear e.g., "2024"
     * @param PeriodType $type Period type to lock
     * @param string $userId User performing the lock
     * @param string $auditReference External audit reference number
     */
    public function lockFiscalYear(
        string $fiscalYear,
        PeriodType $type,
        string $userId,
        string $auditReference
    ): array {
        $periods = $this->periodManager->listPeriods($type, $fiscalYear);

        $results = [
            'locked' => [],
            'already_locked' => [],
            'skipped' => [],
        ];

        foreach ($periods as $period) {
            try {
                match ($period->getStatus()) {
                    PeriodStatus::Locked => $results['already_locked'][] = $period->getName(),
                    PeriodStatus::Open => $results['skipped'][] = $period->getName(),
                    PeriodStatus::Pending => $results['skipped'][] = $period->getName(),
                    PeriodStatus::Closed => $this->lockPeriod($period, $userId, $auditReference, $results),
                };
            } catch (\Exception $e) {
                $results['skipped'][] = $period->getName() . ' (error: ' . $e->getMessage() . ')';
            }
        }

        // Log the year-end lock operation
        $this->auditLogger->log(
            entityId: 'fiscal_year_' . $fiscalYear,
            action: 'year_end_lock',
            description: sprintf(
                'Fiscal year %s locked for %s. Audit ref: %s. Locked: %d, Already locked: %d, Skipped: %d',
                $fiscalYear,
                $type->value,
                $auditReference,
                count($results['locked']),
                count($results['already_locked']),
                count($results['skipped'])
            )
        );

        return $results;
    }

    private function lockPeriod(
        PeriodInterface $period,
        string $userId,
        string $auditReference,
        array &$results
    ): void {
        $this->periodManager->lockPeriod(
            periodId: $period->getId(),
            reason: "Year-end lock. Audit reference: {$auditReference}",
            userId: $userId
        );

        $results['locked'][] = $period->getName();
    }
}

// ============================================
// Example 3: Period Reopening with Authorization
// ============================================

/**
 * Controlled period reopening process
 *
 * Reopening requires special authorization and creates
 * comprehensive audit trail for compliance.
 */
final readonly class PeriodReopenService
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        private AuthorizationInterface $authorization,
        private AuditLoggerInterface $auditLogger
    ) {}

    /**
     * Reopen a closed period with authorization check
     *
     * @throws PeriodReopeningUnauthorizedException If user lacks permission
     * @throws InvalidPeriodStatusException If period cannot be reopened (e.g., locked)
     */
    public function requestReopen(
        string $periodId,
        string $userId,
        string $businessJustification
    ): void {
        // Authorization is checked by PeriodManager, but we can add extra validation

        // Validate business justification
        if (strlen($businessJustification) < 20) {
            throw new \InvalidArgumentException(
                'Business justification must be at least 20 characters'
            );
        }

        // Get period for pre-validation
        $period = $this->periodManager->findById($periodId);

        // Check if period was closed recently (business rule example)
        $closedAt = $this->getClosedAt($period);
        if ($closedAt !== null) {
            $hoursSinceClosed = (new DateTimeImmutable())->diff($closedAt)->h +
                               ((new DateTimeImmutable())->diff($closedAt)->days * 24);

            if ($hoursSinceClosed < 24) {
                // Allow quick reopen without additional approval
                $this->periodManager->reopenPeriod($periodId, $businessJustification, $userId);
                return;
            }

            // If closed > 24 hours, require manager approval (example business rule)
            if (!$this->authorization->canReopenPeriod($userId)) {
                throw new \DomainException(
                    'Period closed > 24 hours ago requires manager approval'
                );
            }
        }

        // Perform the reopen
        $this->periodManager->reopenPeriod(
            periodId: $periodId,
            reason: $businessJustification,
            userId: $userId
        );
    }

    private function getClosedAt(PeriodInterface $period): ?DateTimeImmutable
    {
        // This would be retrieved from period metadata
        // For example, stored when closePeriod was called
        return null;
    }
}

// ============================================
// Example 4: Multi-Period Type Coordination
// ============================================

/**
 * Coordinate multiple period types for ERP operations
 *
 * Some business processes span multiple period types:
 * - Manufacturing closes before Inventory
 * - Inventory closes before Accounting
 * - Payroll has independent schedule
 */
final readonly class PeriodCoordinator
{
    public function __construct(
        private PeriodManagerInterface $periodManager
    ) {}

    /**
     * Check if all required period types are open for a transaction
     */
    public function validateAllPeriodsOpen(
        DateTimeImmutable $transactionDate,
        array $requiredTypes
    ): array {
        $results = [];

        foreach ($requiredTypes as $type) {
            $isOpen = $this->periodManager->isPostingAllowed($transactionDate, $type);

            $results[$type->value] = [
                'type' => $type->label(),
                'is_open' => $isOpen,
                'date' => $transactionDate->format('Y-m-d'),
            ];

            if (!$isOpen) {
                $period = $this->periodManager->findPeriodByDate($transactionDate, $type);
                $results[$type->value]['status'] = $period?->getStatus()->label() ?? 'No period exists';
                $results[$type->value]['period'] = $period?->getName();
            }
        }

        return $results;
    }

    /**
     * Get recommended close sequence
     *
     * Returns period types in recommended closing order
     * based on ERP best practices.
     */
    public function getCloseSequence(): array
    {
        return [
            [
                'order' => 1,
                'type' => PeriodType::Manufacturing,
                'rationale' => 'Close manufacturing first to finalize WIP and production costs',
            ],
            [
                'order' => 2,
                'type' => PeriodType::Inventory,
                'rationale' => 'Close inventory after manufacturing to capture final COGS',
            ],
            [
                'order' => 3,
                'type' => PeriodType::Accounting,
                'rationale' => 'Close accounting last after all inventory values are finalized',
            ],
            [
                'order' => 'Independent',
                'type' => PeriodType::Payroll,
                'rationale' => 'Payroll follows its own schedule (weekly/bi-weekly/monthly)',
            ],
        ];
    }

    /**
     * Get period status dashboard for all types
     */
    public function getDashboard(): array
    {
        $dashboard = [];

        foreach (PeriodType::cases() as $type) {
            $openPeriod = $this->periodManager->getOpenPeriod($type);

            $dashboard[$type->value] = [
                'type' => $type->label(),
                'has_open_period' => $openPeriod !== null,
                'current_period' => $openPeriod?->getName(),
                'period_dates' => $openPeriod !== null
                    ? sprintf(
                        '%s to %s',
                        $openPeriod->getStartDate()->format('Y-m-d'),
                        $openPeriod->getEndDate()->format('Y-m-d')
                    )
                    : null,
            ];
        }

        return $dashboard;
    }
}

// Usage:
// $coordinator = new PeriodCoordinator($periodManager);
//
// // Validate inventory adjustment can be posted
// $results = $coordinator->validateAllPeriodsOpen(
//     new DateTimeImmutable('2024-11-15'),
//     [PeriodType::Inventory, PeriodType::Accounting]
// );
//
// // Check if all periods are open
// $allOpen = collect($results)->every(fn($r) => $r['is_open']);

// ============================================
// Example 5: Integration with Nexus Finance
// ============================================

/**
 * Example showing integration with Nexus\Finance for GL posting
 *
 * This demonstrates the pattern for cross-package integration.
 */
final readonly class JournalEntryPostingService
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        // private GeneralLedgerManagerInterface $glManager (from Nexus\Finance)
    ) {}

    /**
     * Post journal entry with period validation
     *
     * @param array $entryData Journal entry data
     * @throws \DomainException If period is not open
     */
    public function post(array $entryData): void
    {
        $entryDate = new DateTimeImmutable($entryData['date']);

        // Step 1: Validate accounting period is open
        if (!$this->periodManager->isPostingAllowed($entryDate, PeriodType::Accounting)) {
            $period = $this->periodManager->findPeriodByDate($entryDate, PeriodType::Accounting);

            throw new \DomainException(
                sprintf(
                    'Cannot post journal entry: Accounting period %s is %s',
                    $period?->getName() ?? 'unknown',
                    $period?->getStatus()->label() ?? 'not found'
                )
            );
        }

        // Step 2: Get the period for reference
        $period = $this->periodManager->findPeriodByDate($entryDate, PeriodType::Accounting);

        // Step 3: Post to general ledger
        // $this->glManager->post([
        //     ...$entryData,
        //     'period_id' => $period->getId(),
        //     'fiscal_year' => $period->getFiscalYear(),
        // ]);
    }
}

// ============================================
// Example 6: Period Setup Automation
// ============================================

/**
 * Automated period creation for new fiscal year
 */
final readonly class PeriodSetupService
{
    public function __construct(
        private PeriodRepositoryInterface $repository
    ) {}

    /**
     * Generate periods for a fiscal year
     *
     * @param string $fiscalYear e.g., "2025"
     * @param PeriodType $type Period type to create
     * @param string $tenantId Tenant identifier
     * @param string $frequency 'monthly', 'quarterly', 'weekly'
     * @return array Created periods
     */
    public function generateFiscalYear(
        string $fiscalYear,
        PeriodType $type,
        string $tenantId,
        string $frequency = 'monthly'
    ): array {
        $startDate = new DateTimeImmutable("{$fiscalYear}-01-01");
        $periods = [];

        match ($frequency) {
            'monthly' => $periods = $this->generateMonthlyPeriods($startDate, $fiscalYear, $type, $tenantId),
            'quarterly' => $periods = $this->generateQuarterlyPeriods($startDate, $fiscalYear, $type, $tenantId),
            'weekly' => throw new \InvalidArgumentException('Weekly periods not yet implemented'),
            default => throw new \InvalidArgumentException("Unknown frequency: {$frequency}"),
        };

        return $periods;
    }

    private function generateMonthlyPeriods(
        DateTimeImmutable $startDate,
        string $fiscalYear,
        PeriodType $type,
        string $tenantId
    ): array {
        $periods = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = new DateTimeImmutable("{$fiscalYear}-{$month}-01");
            $monthEnd = $monthStart->modify('last day of this month');

            $name = strtoupper($monthStart->format('M')) . '-' . $fiscalYear;

            // Period would be created via repository
            // This is a simplified example showing the structure
            $periods[] = [
                'tenant_id' => $tenantId,
                'type' => $type->value,
                'status' => PeriodStatus::Pending->value,
                'start_date' => $monthStart->format('Y-m-d'),
                'end_date' => $monthEnd->format('Y-m-d'),
                'fiscal_year' => $fiscalYear,
                'name' => $name,
                'description' => sprintf(
                    '%s period for %s %s',
                    $type->label(),
                    $monthStart->format('F'),
                    $fiscalYear
                ),
            ];
        }

        return $periods;
    }

    private function generateQuarterlyPeriods(
        DateTimeImmutable $startDate,
        string $fiscalYear,
        PeriodType $type,
        string $tenantId
    ): array {
        $periods = [];
        $quarters = [
            ['start' => '01-01', 'end' => '03-31', 'name' => 'Q1'],
            ['start' => '04-01', 'end' => '06-30', 'name' => 'Q2'],
            ['start' => '07-01', 'end' => '09-30', 'name' => 'Q3'],
            ['start' => '10-01', 'end' => '12-31', 'name' => 'Q4'],
        ];

        foreach ($quarters as $quarter) {
            $periods[] = [
                'tenant_id' => $tenantId,
                'type' => $type->value,
                'status' => PeriodStatus::Pending->value,
                'start_date' => "{$fiscalYear}-{$quarter['start']}",
                'end_date' => "{$fiscalYear}-{$quarter['end']}",
                'fiscal_year' => $fiscalYear,
                'name' => "{$fiscalYear}-{$quarter['name']}",
                'description' => sprintf(
                    '%s period for %s %s',
                    $type->label(),
                    $quarter['name'],
                    $fiscalYear
                ),
            ];
        }

        return $periods;
    }
}

// ============================================
// Example Output Summary
// ============================================

/*
Multi-Period Dashboard Example:
{
    "accounting": {
        "type": "Accounting",
        "has_open_period": true,
        "current_period": "NOV-2024",
        "period_dates": "2024-11-01 to 2024-11-30"
    },
    "inventory": {
        "type": "Inventory",
        "has_open_period": true,
        "current_period": "2024-Q4",
        "period_dates": "2024-10-01 to 2024-12-31"
    },
    "payroll": {
        "type": "Payroll",
        "has_open_period": true,
        "current_period": "PP-2024-23",
        "period_dates": "2024-11-01 to 2024-11-15"
    },
    "manufacturing": {
        "type": "Manufacturing",
        "has_open_period": true,
        "current_period": "MFG-NOV-2024",
        "period_dates": "2024-11-01 to 2024-11-30"
    }
}

Year-End Lock Results:
{
    "locked": ["JAN-2024", "FEB-2024", ..., "DEC-2024"],
    "already_locked": [],
    "skipped": []
}

Period Validation Results:
{
    "accounting": {
        "type": "Accounting",
        "is_open": true,
        "date": "2024-11-15"
    },
    "inventory": {
        "type": "Inventory",
        "is_open": true,
        "date": "2024-11-15"
    }
}
*/
