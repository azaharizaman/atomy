<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\ActivityType;
use Nexus\CRM\ValueObjects\ActivityDuration;

/**
 * Activity Interface
 * 
 * Represents a CRM activity (call, email, meeting, task, note).
 * Activities track interactions with leads and opportunities.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface ActivityInterface
{
    /**
     * Get unique activity identifier
     */
    public function getId(): string;

    /**
     * Get tenant identifier for multi-tenancy
     */
    public function getTenantId(): string;

    /**
     * Get activity type
     */
    public function getType(): ActivityType;

    /**
     * Get activity title/subject
     */
    public function getTitle(): string;

    /**
     * Get activity description/content
     */
    public function getDescription(): ?string;

    /**
     * Get activity duration
     */
    public function getDuration(): ?ActivityDuration;

    /**
     * Get related entity type (lead, opportunity, etc.)
     */
    public function getRelatedEntityType(): ?string;

    /**
     * Get related entity ID
     */
    public function getRelatedEntityId(): ?string;

    /**
     * Get scheduled start time (for meetings, calls)
     */
    public function getScheduledAt(): ?\DateTimeImmutable;

    /**
     * Get actual start time
     */
    public function getStartedAt(): ?\DateTimeImmutable;

    /**
     * Get actual end time
     */
    public function getEndedAt(): ?\DateTimeImmutable;

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last modification timestamp
     */
    public function getUpdatedAt(): \DateTimeImmutable;

    /**
     * Check if activity is completed
     */
    public function isCompleted(): bool;

    /**
     * Check if activity is overdue
     */
    public function isOverdue(): bool;

    /**
     * Check if activity is scheduled for the future
     */
    public function isScheduled(): bool;
}
