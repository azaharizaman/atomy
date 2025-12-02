<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\AdjustableTime;
use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Contracts\Temporal;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable date range value object.
 * 
 * Represents a period between two dates. Used for reporting periods, contracts, etc.
 */
final readonly class DateRange implements Comparable, Temporal, AdjustableTime, SerializableVO
{
    /**
     * @throws InvalidValueException
     */
    public function __construct(
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate
    ) {
        if ($endDate < $startDate) {
            throw new InvalidValueException('End date must be after or equal to start date');
        }
    }

    // Temporal implementation
    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function contains(\DateTimeImmutable $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function overlaps(Temporal $other): bool
    {
        return $this->startDate <= $other->getEndDate() 
            && $this->endDate >= $other->getStartDate();
    }

    // AdjustableTime implementation
    public function shift(\DateInterval $interval): static
    {
        return new self(
            startDate: $this->startDate->add($interval),
            endDate: $this->endDate->add($interval)
        );
    }

    public function extend(\DateInterval $interval): static
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate->add($interval)
        );
    }

    // Additional utility methods
    public function getDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days;
    }

    public function isActive(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->contains($now);
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another DateRange');
        }

        // Compare by start date first, then end date
        $startComparison = $this->startDate <=> $other->startDate;
        if ($startComparison !== 0) {
            return $startComparison;
        }

        return $this->endDate <=> $other->endDate;
    }

    public function equals(Comparable $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->startDate == $other->startDate 
            && $this->endDate == $other->endDate;
    }

    public function greaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->format('Y-m-d'),
            'end_date' => $this->endDate->format('Y-m-d'),
            'days' => $this->getDays(),
        ];
    }

    public function toString(): string
    {
        return $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d');
    }

    public static function fromArray(array $data): static
    {
        return new self(
            startDate: new \DateTimeImmutable($data['start_date']),
            endDate: new \DateTimeImmutable($data['end_date'])
        );
    }
}
