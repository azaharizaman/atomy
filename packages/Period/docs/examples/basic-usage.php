<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Nexus Period Package
 *
 * This example demonstrates:
 * 1. Checking if a period is open for posting
 * 2. Finding the current open period
 * 3. Validating transaction dates
 * 4. Basic period lifecycle operations
 *
 * @package Nexus\Period
 * @see docs/getting-started.md for initial setup
 */

use DateTimeImmutable;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\PeriodNotFoundException;

// ============================================
// Example 1: Check if Posting is Allowed
// ============================================

/**
 * Service that validates transaction dates before posting
 *
 * The isPostingAllowed() method uses caching for performance
 * (< 5ms response time with Redis/Memcached)
 */
final readonly class TransactionValidator
{
    public function __construct(
        private PeriodManagerInterface $periodManager
    ) {}

    public function validateForPosting(DateTimeImmutable $transactionDate): bool
    {
        // Check if accounting period is open for this date
        $allowed = $this->periodManager->isPostingAllowed(
            date: $transactionDate,
            type: PeriodType::Accounting
        );

        if (!$allowed) {
            // Period is closed, pending, or locked
            throw new \DomainException(
                sprintf(
                    'Cannot post transaction: accounting period is not open for %s',
                    $transactionDate->format('Y-m-d')
                )
            );
        }

        return true;
    }
}

// Usage:
// $validator = new TransactionValidator($periodManager);
// $validator->validateForPosting(new DateTimeImmutable('2024-11-15')); // true or throws

// ============================================
// Example 2: Get Current Open Period
// ============================================

/**
 * Service that retrieves period information for reporting
 */
final readonly class ReportingService
{
    public function __construct(
        private PeriodManagerInterface $periodManager
    ) {}

    public function getCurrentAccountingPeriod(): ?PeriodInterface
    {
        return $this->periodManager->getOpenPeriod(PeriodType::Accounting);
    }

    public function getCurrentInventoryPeriod(): ?PeriodInterface
    {
        return $this->periodManager->getOpenPeriod(PeriodType::Inventory);
    }

    public function getPeriodInfo(PeriodInterface $period): array
    {
        return [
            'id' => $period->getId(),
            'name' => $period->getName(),
            'fiscal_year' => $period->getFiscalYear(),
            'type' => $period->getType()->label(),
            'status' => $period->getStatus()->label(),
            'start_date' => $period->getStartDate()->format('Y-m-d'),
            'end_date' => $period->getEndDate()->format('Y-m-d'),
            'can_post' => $period->isPostingAllowed(),
        ];
    }
}

// Usage:
// $service = new ReportingService($periodManager);
// $period = $service->getCurrentAccountingPeriod();
// echo $period->getName(); // "NOV-2024"

// ============================================
// Example 3: Find Period by Date
// ============================================

/**
 * Find which period a transaction belongs to
 */
final readonly class PeriodLookupService
{
    public function __construct(
        private PeriodManagerInterface $periodManager
    ) {}

    public function findPeriodForTransaction(
        DateTimeImmutable $date,
        PeriodType $type
    ): PeriodInterface {
        $period = $this->periodManager->findPeriodByDate($date, $type);

        if ($period === null) {
            throw new \DomainException(
                sprintf(
                    'No %s period exists for date %s',
                    $type->value,
                    $date->format('Y-m-d')
                )
            );
        }

        return $period;
    }
}

// Usage:
// $service = new PeriodLookupService($periodManager);
// $period = $service->findPeriodForTransaction(
//     new DateTimeImmutable('2024-11-15'),
//     PeriodType::Accounting
// );
// echo $period->getName(); // "NOV-2024"

// ============================================
// Example 4: List Periods by Fiscal Year
// ============================================

/**
 * Retrieve all periods for fiscal year reporting
 */
final readonly class FiscalYearService
{
    public function __construct(
        private PeriodManagerInterface $periodManager
    ) {}

    public function getAccountingPeriods(string $fiscalYear): array
    {
        return $this->periodManager->listPeriods(
            type: PeriodType::Accounting,
            fiscalYear: $fiscalYear
        );
    }

    public function summarizeFiscalYear(string $fiscalYear): array
    {
        $periods = $this->getAccountingPeriods($fiscalYear);

        $summary = [
            'fiscal_year' => $fiscalYear,
            'total_periods' => count($periods),
            'open' => 0,
            'closed' => 0,
            'locked' => 0,
            'pending' => 0,
        ];

        foreach ($periods as $period) {
            match ($period->getStatus()) {
                PeriodStatus::Open => $summary['open']++,
                PeriodStatus::Closed => $summary['closed']++,
                PeriodStatus::Locked => $summary['locked']++,
                PeriodStatus::Pending => $summary['pending']++,
            };
        }

        return $summary;
    }
}

// Usage:
// $service = new FiscalYearService($periodManager);
// $summary = $service->summarizeFiscalYear('2024');
// print_r($summary);
// Output: ['fiscal_year' => '2024', 'total_periods' => 12, 'open' => 1, 'closed' => 10, ...]

// ============================================
// Example 5: Working with Period Status
// ============================================

/**
 * Understanding period statuses
 */
function demonstrateStatuses(): void
{
    // Status: PENDING - Period created but not yet active
    $pending = PeriodStatus::Pending;
    echo $pending->label();           // "Pending"
    echo $pending->isPostingAllowed(); // false

    // Status: OPEN - Active period accepting transactions
    $open = PeriodStatus::Open;
    echo $open->label();              // "Open"
    echo $open->isPostingAllowed();   // true

    // Status: CLOSED - Period closed, can be reopened
    $closed = PeriodStatus::Closed;
    echo $closed->label();            // "Closed"
    echo $closed->isPostingAllowed(); // false

    // Status: LOCKED - Permanently sealed, cannot be reopened
    $locked = PeriodStatus::Locked;
    echo $locked->label();            // "Locked"
    echo $locked->isPostingAllowed(); // false
}

// ============================================
// Example 6: Working with Period Types
// ============================================

/**
 * Understanding period types
 */
function demonstratePeriodTypes(): void
{
    // Each period type is independent - you can have:
    // - Accounting period open for November
    // - Inventory period open for Q4
    // - Payroll period open for pay period 23

    $accounting = PeriodType::Accounting;
    echo $accounting->value; // "accounting"
    echo $accounting->label(); // "Accounting"

    $inventory = PeriodType::Inventory;
    echo $inventory->value; // "inventory"
    echo $inventory->label(); // "Inventory"

    $payroll = PeriodType::Payroll;
    echo $payroll->value; // "payroll"
    echo $payroll->label(); // "Payroll"

    $manufacturing = PeriodType::Manufacturing;
    echo $manufacturing->value; // "manufacturing"
    echo $manufacturing->label(); // "Manufacturing"
}

// ============================================
// Example Output
// ============================================

/*
Expected usage flow:

1. Application starts → inject PeriodManagerInterface
2. User creates invoice with date 2024-11-15
3. System calls isPostingAllowed(date, PeriodType::Accounting)
4. If true → post invoice
5. If false → throw error to user

Example output from getPeriodInfo():
{
    "id": "01JDQHX5K8JNVR3MPBQXY7DCTW",
    "name": "NOV-2024",
    "fiscal_year": "2024",
    "type": "Accounting",
    "status": "Open",
    "start_date": "2024-11-01",
    "end_date": "2024-11-30",
    "can_post": true
}
*/
