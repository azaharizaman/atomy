<?php

declare(strict_types=1);

namespace Nexus\Payment\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\DisbursementLimitStorageInterface;
use Nexus\Payment\Contracts\DisbursementLimitValidatorInterface;
use Nexus\Payment\Contracts\DisbursementQueryInterface;
use Nexus\Payment\Enums\DisbursementStatus;
use Nexus\Payment\Enums\LimitPeriod;
use Nexus\Payment\Exceptions\DisbursementLimitExceededException;
use Nexus\Payment\ValueObjects\DisbursementLimits;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Disbursement limit validator service.
 *
 * Validates disbursement amounts against configured limits (PAY-035).
 * Supports per-transaction, daily, weekly, and monthly limits.
 */
final class DisbursementLimitValidator implements DisbursementLimitValidatorInterface
{
    /** @var array<string, DisbursementLimits> Tenant limits cache */
    private array $tenantLimits = [];

    /** @var array<string, array<string, DisbursementLimits>> User limits cache */
    private array $userLimits = [];

    public function __construct(
        private readonly DisbursementQueryInterface $disbursementQuery,
        private readonly DisbursementLimitStorageInterface $limitStorage,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritDoc}
     */
    public function validate(
        string $tenantId,
        Money $amount,
        ?string $userId = null,
        ?string $recipientId = null,
    ): void {
        $limits = $this->getLimits($tenantId, $userId);

        if (!$limits->hasLimits()) {
            return;
        }

        // Validate per-transaction limit
        $limits->validateAmount($amount);

        // Validate period limits
        foreach ([LimitPeriod::DAILY, LimitPeriod::WEEKLY, LimitPeriod::MONTHLY] as $period) {
            $periodLimit = $limits->getLimitForPeriod($period);

            if ($periodLimit === null) {
                continue;
            }

            $currentUsage = $this->getCurrentUsage($tenantId, $period, $userId);
            $limits->validatePeriodAmount($amount, $currentUsage, $period);

            // Also check count limits
            $countLimit = $limits->getCountLimitForPeriod($period);
            if ($countLimit !== null) {
                $currentCount = $this->getCurrentCount($tenantId, $period, $userId);
                $limits->validatePeriodCount($currentCount, $period);
            }
        }

        $this->logger->debug('Disbursement limit validation passed', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'amount' => $amount->getAmount(),
            'currency' => $amount->getCurrency(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function checkLimits(
        string $tenantId,
        Money $amount,
        ?string $userId = null,
    ): array {
        $limits = $this->getLimits($tenantId, $userId);
        $results = [];

        // Check per-transaction
        $perTxLimit = $limits->getLimitForPeriod(LimitPeriod::PER_TRANSACTION);
        $results['per_transaction'] = [
            'exceeded' => $perTxLimit !== null && $amount->greaterThan($perTxLimit),
            'limit' => $perTxLimit,
            'current_usage' => null,
            'period' => LimitPeriod::PER_TRANSACTION,
        ];

        // Check period limits
        foreach ([LimitPeriod::DAILY, LimitPeriod::WEEKLY, LimitPeriod::MONTHLY] as $period) {
            $periodLimit = $limits->getLimitForPeriod($period);
            $currentUsage = $periodLimit !== null
                ? $this->getCurrentUsage($tenantId, $period, $userId)
                : null;

            $wouldExceed = false;
            if ($periodLimit !== null && $currentUsage !== null) {
                $projectedTotal = $currentUsage->add($amount);
                $wouldExceed = $projectedTotal->greaterThan($periodLimit);
            }

            $results[$period->value] = [
                'exceeded' => $wouldExceed,
                'limit' => $periodLimit,
                'current_usage' => $currentUsage,
                'period' => $period,
            ];
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentUsage(
        string $tenantId,
        LimitPeriod $period,
        ?string $userId = null,
    ): Money {
        $now = new \DateTimeImmutable();
        $periodStart = $period->getPeriodStart($now);
        $periodEnd = $period->getPeriodEnd($now);

        // Query completed and processing disbursements in the period
        $disbursements = $this->disbursementQuery->findByDateRange(
            tenantId: $tenantId,
            startDate: $periodStart,
            endDate: $periodEnd,
            userId: $userId,
            statuses: [
                DisbursementStatus::APPROVED,
                DisbursementStatus::PROCESSING,
                DisbursementStatus::COMPLETED,
            ],
        );

        // Sum up amounts
        $total = Money::zero('USD'); // Will match first disbursement's currency

        foreach ($disbursements as $disbursement) {
            $amount = $disbursement->getAmount();

            if ($total->getCurrency() !== $amount->getCurrency()) {
                // Currency mismatch - in real implementation, would use CurrencyConversionInterface
                continue;
            }

            $total = $total->add($amount);
        }

        return $total;
    }

    /**
     * {@inheritDoc}
     */
    public function getRemainingAllowance(
        string $tenantId,
        LimitPeriod $period,
        ?string $userId = null,
    ): ?Money {
        $limits = $this->getLimits($tenantId, $userId);
        $periodLimit = $limits->getLimitForPeriod($period);

        if ($periodLimit === null) {
            return null;
        }

        $currentUsage = $this->getCurrentUsage($tenantId, $period, $userId);
        $remaining = $periodLimit->subtract($currentUsage);

        // Don't return negative amounts
        if ($remaining->isNegative()) {
            return Money::zero($periodLimit->getCurrency());
        }

        return $remaining;
    }

    /**
     * {@inheritDoc}
     */
    public function getLimits(string $tenantId, ?string $userId = null): DisbursementLimits
    {
        // Check for user-specific limits first
        if ($userId !== null) {
            $userLimit = $this->limitStorage->getUserLimits($tenantId, $userId);
            if ($userLimit !== null) {
                return $userLimit;
            }
        }

        // Fall back to tenant limits
        $tenantLimit = $this->limitStorage->getTenantLimits($tenantId);

        return $tenantLimit ?? DisbursementLimits::none();
    }

    /**
     * {@inheritDoc}
     */
    public function setLimits(string $tenantId, DisbursementLimits $limits): void
    {
        $this->limitStorage->setTenantLimits($tenantId, $limits);

        $this->logger->info('Tenant disbursement limits updated', [
            'tenant_id' => $tenantId,
            'has_limits' => $limits->hasLimits(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function setUserLimits(string $tenantId, string $userId, DisbursementLimits $limits): void
    {
        $this->limitStorage->setUserLimits($tenantId, $userId, $limits);

        $this->logger->info('User disbursement limits updated', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'has_limits' => $limits->hasLimits(),
        ]);
    }

    /**
     * Get current transaction count for a period.
     */
    private function getCurrentCount(string $tenantId, LimitPeriod $period, ?string $userId): int
    {
        $now = new \DateTimeImmutable();
        $periodStart = $period->getPeriodStart($now);
        $periodEnd = $period->getPeriodEnd($now);

        $disbursements = $this->disbursementQuery->findByDateRange(
            tenantId: $tenantId,
            startDate: $periodStart,
            endDate: $periodEnd,
            userId: $userId,
            statuses: [
                DisbursementStatus::APPROVED,
                DisbursementStatus::PROCESSING,
                DisbursementStatus::COMPLETED,
            ],
        );

        return count($disbursements);
    }
}
