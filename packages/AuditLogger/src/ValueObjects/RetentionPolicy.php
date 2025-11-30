<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\ValueObjects;

use Nexus\AuditLogger\Exceptions\InvalidRetentionPolicyException;

/**
 * Immutable value object representing audit log retention policy
 * Satisfies: BUS-AUD-0147, FUN-AUD-0194
 *
 * @package Nexus\AuditLogger\ValueObjects
 */
final class RetentionPolicy
{
    public const DEFAULT_DAYS = 90;
    public const MINIMUM_DAYS = 1;

    private int $days;

    /**
     * @param int $days Number of days to retain logs (cannot be negative)
     * @throws InvalidRetentionPolicyException
     */
    public function __construct(int $days = self::DEFAULT_DAYS)
    {
        if ($days < 0) {
            throw new InvalidRetentionPolicyException($days);
        }

        $this->days = $days;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    /**
     * Calculate expiration date from a given creation date
     *
     * @param \DateTimeInterface $createdAt
     * @return \DateTimeInterface
     */
    public function calculateExpirationDate(\DateTimeInterface $createdAt): \DateTimeInterface
    {
        $expiresAt = (new \DateTime($createdAt->format('Y-m-d H:i:s')));
        $expiresAt->modify("+{$this->days} days");
        return $expiresAt;
    }

    /**
     * Check if a log is expired based on its creation date
     *
     * @param \DateTimeInterface $createdAt
     * @param \DateTimeInterface|null $now Current time (defaults to now)
     * @return bool
     */
    public function isExpired(\DateTimeInterface $createdAt, ?\DateTimeInterface $now = null): bool
    {
        $now = $now ?? new \DateTime();
        $expiresAt = $this->calculateExpirationDate($createdAt);
        return $now >= $expiresAt;
    }

    public function __toString(): string
    {
        return "{$this->days} days";
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_DAYS);
    }

    public static function permanent(): self
    {
        return new self(PHP_INT_MAX);
    }

    public static function days(int $days): self
    {
        return new self($days);
    }
}
