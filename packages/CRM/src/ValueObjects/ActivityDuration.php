<?php

declare(strict_types=1);

namespace Nexus\CRM\ValueObjects;

/**
 * Activity Duration Value Object
 * 
 * Represents the duration of a CRM activity.
 * Immutable value object for time tracking.
 * 
 * @package Nexus\CRM\ValueObjects
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class ActivityDuration
{
    /**
     * @param int $minutes Duration in minutes
     */
    public function __construct(
        public int $minutes
    ) {
        if ($minutes < 0) {
            throw new \InvalidArgumentException('Duration cannot be negative');
        }
    }

    /**
     * Create from minutes
     */
    public static function fromMinutes(int $minutes): self
    {
        return new self($minutes);
    }

    /**
     * Create from hours
     */
    public static function fromHours(float $hours): self
    {
        return new self((int) round($hours * 60));
    }

    /**
     * Create from hours and minutes
     */
    public static function fromHoursAndMinutes(int $hours, int $minutes = 0): self
    {
        return new self(($hours * 60) + $minutes);
    }

    /**
     * Create from seconds
     */
    public static function fromSeconds(int $seconds): self
    {
        return new self((int) round($seconds / 60));
    }

    /**
     * Get duration in minutes
     */
    public function getMinutes(): int
    {
        return $this->minutes;
    }

    /**
     * Get duration in hours (decimal)
     */
    public function getHours(): float
    {
        return $this->minutes / 60;
    }

    /**
     * Get duration in seconds
     */
    public function getSeconds(): int
    {
        return $this->minutes * 60;
    }

    /**
     * Get hours component (whole hours only)
     */
    public function getHoursComponent(): int
    {
        return (int) floor($this->minutes / 60);
    }

    /**
     * Get remaining minutes component (after hours)
     */
    public function getMinutesComponent(): int
    {
        return $this->minutes % 60;
    }

    /**
     * Format as human-readable string (e.g., "1h 30m")
     */
    public function format(): string
    {
        $hours = $this->getHoursComponent();
        $minutes = $this->getMinutesComponent();

        if ($hours === 0) {
            return "{$minutes}m";
        }

        if ($minutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$minutes}m";
    }

    /**
     * Format as HH:MM string
     */
    public function formatHHMM(): string
    {
        return sprintf('%02d:%02d', $this->getHoursComponent(), $this->getMinutesComponent());
    }

    /**
     * Check if duration is zero
     */
    public function isZero(): bool
    {
        return $this->minutes === 0;
    }

    /**
     * Check if duration is short (< 15 minutes)
     */
    public function isShort(): bool
    {
        return $this->minutes < 15;
    }

    /**
     * Check if duration is medium (15-60 minutes)
     */
    public function isMedium(): bool
    {
        return $this->minutes >= 15 && $this->minutes <= 60;
    }

    /**
     * Check if duration is long (> 60 minutes)
     */
    public function isLong(): bool
    {
        return $this->minutes > 60;
    }

    /**
     * Add another duration
     */
    public function add(self $other): self
    {
        return new self($this->minutes + $other->minutes);
    }

    /**
     * Subtract another duration
     */
    public function subtract(self $other): self
    {
        return new self(max(0, $this->minutes - $other->minutes));
    }

    /**
     * Compare with another duration
     */
    public function isLongerThan(self $other): bool
    {
        return $this->minutes > $other->minutes;
    }

    /**
     * Compare with another duration
     */
    public function isShorterThan(self $other): bool
    {
        return $this->minutes < $other->minutes;
    }

    /**
     * Check if durations are equal
     */
    public function equals(self $other): bool
    {
        return $this->minutes === $other->minutes;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}