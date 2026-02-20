<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\OpportunityStage;

/**
 * Opportunity Query Interface
 * 
 * Provides read-only query operations for opportunities.
 * Implements CQRS query separation pattern.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface OpportunityQueryInterface
{
    /**
     * Find opportunity by ID
     */
    public function findById(string $id): ?OpportunityInterface;

    /**
     * Find opportunity by ID or throw exception
     * 
     * @throws \Nexus\CRM\Exceptions\OpportunityNotFoundException
     */
    public function findByIdOrFail(string $id): OpportunityInterface;

    /**
     * Find opportunities by pipeline
     * 
     * @return iterable<OpportunityInterface>
     */
    public function findByPipeline(string $pipelineId): iterable;

    /**
     * Find opportunities by stage
     * 
     * @return iterable<OpportunityInterface>
     */
    public function findByStage(OpportunityStage $stage): iterable;

    /**
     * Find open opportunities
     * 
     * @return iterable<OpportunityInterface>
     */
    public function findOpen(): iterable;

    /**
     * Find won opportunities
     * 
     * @return iterable<OpportunityInterface>
     */
    public function findWon(): iterable;

    /**
     * Find lost opportunities
     * 
     * @return iterable<OpportunityInterface>
     */
    public function findLost(): iterable;

    /**
     * Find opportunities closing within date range
     * 
     * @return iterable<OpportunityInterface>
     */
    public function findByExpectedCloseDate(\DateTimeImmutable $from, \DateTimeImmutable $to): iterable;

    /**
     * Find opportunities by minimum value
     * 
     * @return iterable<OpportunityInterface>
     */
    public function findByMinimumValue(int $minimumValue): iterable;

    /**
     * Find stale opportunities (in stage too long)
     * 
     * @return iterable<OpportunityInterface>
     */
    public function findStale(int $maxDaysInStage): iterable;

    /**
     * Find opportunities created from a specific lead
     */
    public function findBySourceLead(string $leadId): ?OpportunityInterface;

    /**
     * Count opportunities by stage
     */
    public function countByStage(OpportunityStage $stage): int;

    /**
     * Get total value of open opportunities
     */
    public function getTotalOpenValue(): int;

    /**
     * Get weighted pipeline value (sum of weighted values)
     */
    public function getWeightedPipelineValue(): int;
}
