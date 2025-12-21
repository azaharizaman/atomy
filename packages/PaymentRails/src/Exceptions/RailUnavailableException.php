<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

/**
 * Exception thrown when a payment rail is unavailable.
 */
final class RailUnavailableException extends PaymentRailException
{
    /**
     * @param string $railType
     * @param string $reason
     * @param \DateTimeImmutable|null $expectedAvailability
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $railType,
        string $reason,
        private readonly ?\DateTimeImmutable $expectedAvailability = null,
        ?\Throwable $previous = null,
    ) {
        $message = "Payment rail '{$railType}' is unavailable: {$reason}";

        if ($this->expectedAvailability !== null) {
            $message .= " Expected availability: {$this->expectedAvailability->format('Y-m-d H:i:s T')}";
        }

        parent::__construct(
            message: $message,
            railType: $railType,
            context: [
                'reason' => $reason,
                'expected_availability' => $this->expectedAvailability?->format(\DateTimeInterface::RFC3339),
            ],
            previous: $previous,
        );
    }

    /**
     * Get the expected availability time.
     */
    public function getExpectedAvailability(): ?\DateTimeImmutable
    {
        return $this->expectedAvailability;
    }

    /**
     * Create for maintenance window.
     */
    public static function maintenance(
        string $railType,
        \DateTimeImmutable $expectedEndTime,
    ): self {
        return new self(
            railType: $railType,
            reason: 'Scheduled maintenance',
            expectedAvailability: $expectedEndTime,
        );
    }

    /**
     * Create for outside operating hours.
     */
    public static function outsideOperatingHours(
        string $railType,
        \DateTimeImmutable $nextOpenTime,
    ): self {
        return new self(
            railType: $railType,
            reason: 'Outside operating hours',
            expectedAvailability: $nextOpenTime,
        );
    }

    /**
     * Create for system outage.
     */
    public static function systemOutage(string $railType): self
    {
        return new self(
            railType: $railType,
            reason: 'System outage',
            expectedAvailability: null,
        );
    }

    /**
     * Create for cutoff time passed.
     */
    public static function cutoffPassed(
        string $railType,
        \DateTimeImmutable $nextCutoff,
    ): self {
        return new self(
            railType: $railType,
            reason: 'Cutoff time has passed for today',
            expectedAvailability: $nextCutoff,
        );
    }
}
