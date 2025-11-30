<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

/**
 * Session activity value object
 * 
 * Immutable representation of session activity data
 */
final readonly class SessionActivity
{
    /**
     * Create new session activity
     * 
     * @param string $sessionId Session identifier
     * @param \DateTimeInterface $lastActivityAt Last activity timestamp
     * @param string|null $activityType Type of activity (e.g., READ_INVOICE, UPDATE_PROFILE)
     * @param array<string, mixed> $metadata Additional activity metadata
     */
    public function __construct(
        public string $sessionId,
        public \DateTimeInterface $lastActivityAt,
        public ?string $activityType = null,
        public array $metadata = []
    ) {
        if (empty($this->sessionId)) {
            throw new \InvalidArgumentException('Session ID cannot be empty');
        }
    }

    /**
     * Create activity record for current time
     * 
     * Convenience factory for creating a session activity record with the current timestamp.
     * 
     * @param string $sessionId Session identifier
     * @param string|null $activityType Type of activity (e.g., READ_INVOICE, UPDATE_PROFILE)
     * @param array<string, mixed> $metadata Additional activity metadata
     * @return self
     */
    public static function now(string $sessionId, ?string $activityType = null, array $metadata = []): self
    {
        return new self(
            sessionId: $sessionId,
            lastActivityAt: new \DateTimeImmutable(),
            activityType: $activityType,
            metadata: $metadata
        );
    }

    /**
     * Check if activity is recent (within specified minutes)
     */
    public function isRecent(int $minutes = 30): bool
    {
        $now = new \DateTimeImmutable();
        $threshold = $now->modify("-{$minutes} minutes");
        
        return $this->lastActivityAt >= $threshold;
    }

    /**
     * Get time since activity in seconds
     */
    public function getTimeSinceActivity(): int
    {
        $now = new \DateTimeImmutable();
        return $now->getTimestamp() - $this->lastActivityAt->getTimestamp();
    }

    /**
     * Convert to array
     * 
     * @return array{session_id: string, last_activity_at: string, activity_type: string|null, metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'last_activity_at' => $this->lastActivityAt->format('Y-m-d H:i:s'),
            'activity_type' => $this->activityType,
            'metadata' => $this->metadata,
        ];
    }
}
