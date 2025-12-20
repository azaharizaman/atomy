<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\LimitPeriod;
use Nexus\Payment\ValueObjects\DisbursementLimits;

/**
 * Contract for disbursement limit validation service.
 *
 * Validates disbursement amounts against configured limits.
 * Used for PAY-035: Disbursement limits and controls.
 */
interface DisbursementLimitValidatorInterface
{
    /**
     * Validate a disbursement amount against all applicable limits.
     *
     * @param string $tenantId Tenant ID
     * @param Money $amount Amount to validate
     * @param string|null $userId User ID (for user-specific limits)
     * @param string|null $recipientId Recipient ID (for recipient-specific limits)
     *
     * @throws \Nexus\Payment\Exceptions\DisbursementLimitExceededException If any limit is exceeded
     */
    public function validate(
        string $tenantId,
        Money $amount,
        ?string $userId = null,
        ?string $recipientId = null,
    ): void;

    /**
     * Check if a disbursement amount would exceed limits (without throwing).
     *
     * @param string $tenantId Tenant ID
     * @param Money $amount Amount to check
     * @param string|null $userId User ID (for user-specific limits)
     *
     * @return array<string, array{exceeded: bool, limit: Money|null, current_usage: Money|null, period: LimitPeriod}>
     */
    public function checkLimits(
        string $tenantId,
        Money $amount,
        ?string $userId = null,
    ): array;

    /**
     * Get current usage for a specific period.
     *
     * @param string $tenantId Tenant ID
     * @param LimitPeriod $period Period to check
     * @param string|null $userId User ID for user-specific usage
     *
     * @return Money Current usage amount for the period
     */
    public function getCurrentUsage(
        string $tenantId,
        LimitPeriod $period,
        ?string $userId = null,
    ): Money;

    /**
     * Get remaining allowance for a specific period.
     *
     * @param string $tenantId Tenant ID
     * @param LimitPeriod $period Period to check
     * @param string|null $userId User ID for user-specific limits
     *
     * @return Money|null Remaining allowance (null if no limit configured)
     */
    public function getRemainingAllowance(
        string $tenantId,
        LimitPeriod $period,
        ?string $userId = null,
    ): ?Money;

    /**
     * Get limits configuration for a tenant.
     *
     * @param string $tenantId Tenant ID
     * @param string|null $userId User ID for user-specific limits
     *
     * @return DisbursementLimits The configured limits
     */
    public function getLimits(string $tenantId, ?string $userId = null): DisbursementLimits;

    /**
     * Set limits for a tenant.
     *
     * @param string $tenantId Tenant ID
     * @param DisbursementLimits $limits The limits to set
     */
    public function setLimits(string $tenantId, DisbursementLimits $limits): void;

    /**
     * Set user-specific limits.
     *
     * @param string $tenantId Tenant ID
     * @param string $userId User ID
     * @param DisbursementLimits $limits The limits to set
     */
    public function setUserLimits(string $tenantId, string $userId, DisbursementLimits $limits): void;
}
