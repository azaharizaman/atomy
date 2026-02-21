<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Exceptions;

/**
 * Exception thrown when a depreciation schedule is not found.
 *
 * This exception is raised when:
 * - Looking up a schedule by ID that doesn't exist
 * - Attempting to access an asset's schedule when none exists
 * - Trying to modify a non-existent schedule
 *
 * @package Nexus\FixedAssetDepreciation\Exceptions
 */
class ScheduleNotFoundException extends DepreciationException
{
    protected string $errorCode = 'SCHEDULE_NOT_FOUND';

    /**
     * @param string $scheduleId The schedule identifier that was not found
     * @param string|null $assetId Optional asset ID for context
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        public readonly string $scheduleId,
        public readonly ?string $assetId = null,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Depreciation schedule not found: %s',
            $scheduleId
        );
        if ($assetId !== null) {
            $message .= sprintf(' (asset: %s)', $assetId);
        }
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create from schedule ID.
     *
     * @param string $scheduleId Schedule ID
     * @return self
     */
    public static function withId(string $scheduleId): self
    {
        return new self($scheduleId);
    }

    /**
     * Create from asset ID.
     *
     * When looking for an asset's schedule but none exists.
     *
     * @param string $assetId Asset ID
     * @return self
     */
    public static function forAsset(string $assetId): self
    {
        return new self(
            'no-schedule',
            $assetId,
            new \RuntimeException(sprintf(
                'No depreciation schedule exists for asset %s',
                $assetId
            ))
        );
    }

    /**
     * Create when trying to adjust a non-existent schedule.
     *
     * @param string $scheduleId Schedule ID
     * @return self
     */
    public static function forAdjustment(string $scheduleId): self
    {
        $ex = new self($scheduleId);
        $ex->errorCode = 'SCHEDULE_NOT_FOUND_FOR_ADJUSTMENT';
        return $ex;
    }

    /**
     * Create when trying to close a non-existent schedule.
     *
     * @param string $scheduleId Schedule ID
     * @return self
     */
    public static function forClose(string $scheduleId): self
    {
        $ex = new self($scheduleId);
        $ex->errorCode = 'SCHEDULE_NOT_FOUND_FOR_CLOSE';
        return $ex;
    }

    /**
     * Get the schedule ID.
     *
     * @return string
     */
    public function getScheduleId(): string
    {
        return $this->scheduleId;
    }

    /**
     * Get the asset ID if available.
     *
     * @return string|null
     */
    public function getAssetId(): ?string
    {
        return $this->assetId;
    }
}
