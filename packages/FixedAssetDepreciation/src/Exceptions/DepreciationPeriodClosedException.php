<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Exceptions;

/**
 * Exception thrown when attempting to process depreciation for a closed period.
 *
 * This exception is raised when:
 * - Attempting to calculate depreciation in a closed fiscal period
 * - Attempting to post depreciation to a closed period
 * - Trying to modify depreciation records in a closed period
 *
 * @package Nexus\FixedAssetDepreciation\Exceptions
 */
class DepreciationPeriodClosedException extends DepreciationException
{
    protected string $errorCode = 'DEPRECIATION_PERIOD_CLOSED';

    /**
     * @param string $periodId The closed period identifier
     * @param string $operation The attempted operation
     * @param string|null $message Optional custom message
     */
    public function __construct(
        public readonly string $periodId,
        public readonly string $operation,
        ?string $message = null,
        ?\Throwable $previous = null
    ) {
        $msg = $message ?? sprintf(
            'Cannot %s depreciation for period %s: period is closed',
            $operation,
            $periodId
        );
        parent::__construct($msg, 0, $previous);
    }

    /**
     * Create from period ID and operation.
     *
     * @param string $periodId Period ID
     * @param string $operation Operation attempted
     * @return self
     */
    public static function forPeriod(string $periodId, string $operation): self
    {
        return new self($periodId, $operation);
    }

    /**
     * Create for calculate operation.
     *
     * @param string $periodId Period ID
     * @return self
     */
    public static function forCalculate(string $periodId): self
    {
        return new self($periodId, 'calculate');
    }

    /**
     * Create for post operation.
     *
     * @param string $periodId Period ID
     * @return self
     */
    public static function forPost(string $periodId): self
    {
        return new self($periodId, 'post');
    }

    /**
     * Create for reverse operation.
     *
     * @param string $periodId Period ID
     * @return self
     */
    public static function forReverse(string $periodId): self
    {
        return new self($periodId, 'reverse');
    }

    /**
     * Create for adjust operation.
     *
     * @param string $periodId Period ID
     * @return self
     */
    public static function forAdjust(string $periodId): self
    {
        return new self($periodId, 'adjust');
    }

    /**
     * Get the period ID.
     *
     * @return string
     */
    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    /**
     * Get the operation that was attempted.
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
}
