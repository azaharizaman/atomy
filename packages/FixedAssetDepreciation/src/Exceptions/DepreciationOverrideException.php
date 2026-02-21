<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Exceptions;

/**
 * Exception thrown when depreciation override is not allowed.
 *
 * This exception is raised when:
 * - Attempting to override depreciation that has already been posted
 * - Trying to change parameters that cannot be changed after posting
 * - Invalid override authorization
 *
 * @package Nexus\FixedAssetDepreciation\Exceptions
 */
class DepreciationOverrideException extends DepreciationException
{
    protected string $errorCode = 'DEPRECIATION_OVERRIDE_NOT_ALLOWED';

    /**
     * @param string $depreciationId The depreciation identifier
     * @param string $reason Reason why override is not allowed
     * @param string|null $currentStatus Current status of the depreciation
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        public readonly string $depreciationId,
        public readonly string $reason,
        public readonly ?string $currentStatus = null,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Cannot override depreciation %s: %s',
            $depreciationId,
            $reason
        );
        if ($currentStatus !== null) {
            $message .= sprintf(' (current status: %s)', $currentStatus);
        }
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create when depreciation is already posted.
     *
     * @param string $depreciationId Depreciation ID
     * @return self
     */
    public static function alreadyPosted(string $depreciationId): self
    {
        return new self(
            $depreciationId,
            'depreciation has already been posted to the general ledger',
            'posted'
        );
    }

    /**
     * Create when depreciation is already reversed.
     *
     * @param string $depreciationId Depreciation ID
     * @return self
     */
    public static function alreadyReversed(string $depreciationId): self
    {
        return new self(
            $depreciationId,
            'depreciation has already been reversed',
            'reversed'
        );
    }

    /**
     * Create when period is closed.
     *
     * @param string $depreciationId Depreciation ID
     * @param string $periodId Period ID
     * @return self
     */
    public static function periodClosed(string $depreciationId, string $periodId): self
    {
        return new self(
            $depreciationId,
            sprintf('period %s is closed', $periodId),
            'posted'
        );
    }

    /**
     * Create when schedule is locked.
     *
     * @param string $depreciationId Depreciation ID
     * @param string $processId Process holding the lock
     * @return self
     */
    public static function scheduleLocked(string $depreciationId, string $processId): self
    {
        return new self(
            $depreciationId,
            sprintf('schedule is locked by process %s', $processId)
        );
    }

    /**
     * Create for unauthorized override attempt.
     *
     * @param string $depreciationId Depreciation ID
     * @param string $user User attempting override
     * @return self
     */
    public static function unauthorized(string $depreciationId, string $user): self
    {
        return new self(
            $depreciationId,
            sprintf('user %s is not authorized to override', $user)
        );
    }

    /**
     * Create for invalid override value.
     *
     * @param string $depreciationId Depreciation ID
     * @param float $overrideValue Attempted override value
     * @param float $currentValue Current depreciation value
     * @return self
     */
    public static function invalidValue(
        string $depreciationId,
        float $overrideValue,
        float $currentValue
    ): self {
        return new self(
            $depreciationId,
            sprintf(
                'invalid override value %.2f (current: %.2f)',
                $overrideValue,
                $currentValue
            )
        );
    }

    /**
     * Get the depreciation ID.
     *
     * @return string
     */
    public function getDepreciationId(): string
    {
        return $this->depreciationId;
    }

    /**
     * Get the reason.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get current status.
     *
     * @return string|null
     */
    public function getCurrentStatus(): ?string
    {
        return $this->currentStatus;
    }
}
