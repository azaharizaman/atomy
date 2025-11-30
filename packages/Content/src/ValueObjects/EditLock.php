<?php

declare(strict_types=1);

namespace Nexus\Content\ValueObjects;

use Nexus\Content\Exceptions\InvalidContentException;

/**
 * Content edit lock value object (L3.2)
 * 
 * Prevents simultaneous editing by tracking who is currently editing.
 * 
 * @property-read string $lockedByUserId
 * @property-read \DateTimeImmutable $lockedAt
 * @property-read \DateTimeImmutable $expiresAt
 */
final readonly class EditLock
{
    private const DEFAULT_LOCK_DURATION_MINUTES = 30;

    /**
     * @param string $lockedByUserId
     * @param \DateTimeImmutable $lockedAt
     * @param \DateTimeImmutable $expiresAt
     */
    private function __construct(
        public string $lockedByUserId,
        public \DateTimeImmutable $lockedAt,
        public \DateTimeImmutable $expiresAt,
    ) {
        if (empty($this->lockedByUserId)) {
            throw new InvalidContentException('Locked by user ID cannot be empty');
        }

        if ($this->expiresAt <= $this->lockedAt) {
            throw new InvalidContentException('Lock expiry must be after lock time');
        }
    }

    /**
     * Create new edit lock
     */
    public static function create(
        string $userId,
        int $durationMinutes = self::DEFAULT_LOCK_DURATION_MINUTES
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            lockedByUserId: $userId,
            lockedAt: $now,
            expiresAt: $now->modify("+{$durationMinutes} minutes"),
        );
    }

    /**
     * Check if lock is expired
     */
    public function isExpired(\DateTimeImmutable $currentTime): bool
    {
        return $currentTime >= $this->expiresAt;
    }

    /**
     * Check if specific user owns this lock
     */
    public function isOwnedBy(string $userId): bool
    {
        return $this->lockedByUserId === $userId;
    }

    /**
     * Extend lock expiration
     */
    public function extend(int $additionalMinutes = self::DEFAULT_LOCK_DURATION_MINUTES): self
    {
        return new self(
            lockedByUserId: $this->lockedByUserId,
            lockedAt: $this->lockedAt,
            expiresAt: $this->expiresAt->modify("+{$additionalMinutes} minutes"),
        );
    }
}
