<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\ActivityType;

/**
 * Activity Query Interface
 * 
 * Provides read-only query operations for activities.
 * Implements CQRS query separation pattern.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface ActivityQueryInterface
{
    /**
     * Find activity by ID
     */
    public function findById(string $id): ?ActivityInterface;

    /**
     * Find activity by ID or throw exception
     * 
     * @throws \Nexus\CRM\Exceptions\ActivityNotFoundException
     */
    public function findByIdOrFail(string $id): ActivityInterface;

    /**
     * Find activities by type
     * 
     * @return iterable<ActivityInterface>
     */
    public function findByType(ActivityType $type): iterable;

    /**
     * Find activities for a specific entity
     * 
     * @return iterable<ActivityInterface>
     */
    public function findByRelatedEntity(string $entityType, string $entityId): iterable;

    /**
     * Find activities for a lead
     * 
     * @return iterable<ActivityInterface>
     */
    public function findByLead(string $leadId): iterable;

    /**
     * Find activities for an opportunity
     * 
     * @return iterable<ActivityInterface>
     */
    public function findByOpportunity(string $opportunityId): iterable;

    /**
     * Find activities within date range
     * 
     * @return iterable<ActivityInterface>
     */
    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): iterable;

    /**
     * Find scheduled activities
     * 
     * @return iterable<ActivityInterface>
     */
    public function findScheduled(): iterable;

    /**
     * Find overdue activities
     * 
     * @return iterable<ActivityInterface>
     */
    public function findOverdue(): iterable;

    /**
     * Find completed activities
     * 
     * @return iterable<ActivityInterface>
     */
    public function findCompleted(): iterable;

    /**
     * Find pending activities (not completed)
     * 
     * @return iterable<ActivityInterface>
     */
    public function findPending(): iterable;

    /**
     * Count activities by type
     */
    public function countByType(ActivityType $type): int;

    /**
     * Count activities for entity
     */
    public function countByRelatedEntity(string $entityType, string $entityId): int;
}
