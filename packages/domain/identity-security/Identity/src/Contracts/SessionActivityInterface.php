<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Session activity interface
 * 
 * Represents session activity tracking data
 */
interface SessionActivityInterface
{
    /**
     * Get session identifier
     */
    public function getSessionId(): string;

    /**
     * Get last activity timestamp
     */
    public function getLastActivityAt(): \DateTimeInterface;

    /**
     * Get activity type (e.g., READ_INVOICE, UPDATE_PROFILE)
     */
    public function getActivityType(): ?string;

    /**
     * Get activity metadata
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
