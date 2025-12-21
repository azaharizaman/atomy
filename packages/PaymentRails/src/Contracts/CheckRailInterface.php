<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\PaymentRails\DTOs\CheckRequest;
use Nexus\PaymentRails\DTOs\CheckResult;
use Nexus\PaymentRails\ValueObjects\Check;
use Nexus\PaymentRails\ValueObjects\CheckNumber;

/**
 * Contract for Check rail operations.
 *
 * Extends the base payment rail with check-specific operations
 * including check issuance, positive pay, and void/stop payment.
 */
interface CheckRailInterface extends PaymentRailInterface
{
    /**
     * Issue a new check.
     */
    public function issueCheck(CheckRequest $request): CheckResult;

    /**
     * Get a check by its check number.
     */
    public function getCheck(CheckNumber $checkNumber): ?Check;

    /**
     * Get a check by its identifier.
     */
    public function getCheckById(string $checkId): ?Check;

    /**
     * Void a check.
     */
    public function voidCheck(string $checkId, string $reason): CheckResult;

    /**
     * Stop payment on a check.
     */
    public function stopPayment(string $checkId, string $reason): CheckResult;

    /**
     * Mark a check as cleared.
     */
    public function markCleared(string $checkId, \DateTimeImmutable $clearedDate): CheckResult;

    /**
     * Mark a check as printed.
     */
    public function markPrinted(string $checkId): CheckResult;

    /**
     * Mark a check as mailed.
     */
    public function markMailed(string $checkId, ?string $trackingNumber = null): CheckResult;

    /**
     * Get the next available check number.
     */
    public function getNextCheckNumber(): CheckNumber;

    /**
     * Generate a positive pay file.
     *
     * @param array<Check> $checks Checks to include
     */
    public function generatePositivePayFile(array $checks): string;

    /**
     * Get outstanding checks.
     *
     * @param int|null $staleDays Consider checks stale after this many days
     * @return array<Check>
     */
    public function getOutstandingChecks(?int $staleDays = null): array;

    /**
     * Get stale-dated checks.
     *
     * @param int $staleDays Number of days after which a check is stale
     * @return array<Check>
     */
    public function getStaleChecks(int $staleDays = 180): array;
}
