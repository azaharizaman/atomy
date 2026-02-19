<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\OpportunityStage;

/**
 * Opportunity Persist Interface
 * 
 * Provides write operations for opportunities.
 * Implements CQRS command separation pattern.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface OpportunityPersistInterface
{
    /**
     * Create a new opportunity
     */
    public function create(
        string $tenantId,
        string $pipelineId,
        string $title,
        int $value,
        string $currency,
        \DateTimeImmutable $expectedCloseDate,
        ?string $description = null,
        ?string $sourceLeadId = null
    ): OpportunityInterface;

    /**
     * Update opportunity details
     */
    public function update(
        string $id,
        ?string $title = null,
        ?string $description = null,
        ?int $value = null,
        ?string $currency = null,
        ?\DateTimeImmutable $expectedCloseDate = null
    ): OpportunityInterface;

    /**
     * Advance opportunity to next stage
     * 
     * @throws \Nexus\CRM\Exceptions\InvalidStageTransitionException
     */
    public function advanceStage(string $id): OpportunityInterface;

    /**
     * Move opportunity to specific stage
     * 
     * @throws \Nexus\CRM\Exceptions\InvalidStageTransitionException
     */
    public function moveToStage(string $id, OpportunityStage $stage): OpportunityInterface;

    /**
     * Mark opportunity as won
     */
    public function markAsWon(string $id, ?int $actualValue = null): OpportunityInterface;

    /**
     * Mark opportunity as lost with reason
     */
    public function markAsLost(string $id, string $reason): OpportunityInterface;

    /**
     * Reopen a closed opportunity
     */
    public function reopen(string $id, OpportunityStage $stage): OpportunityInterface;

    /**
     * Delete opportunity (soft delete)
     */
    public function delete(string $id): void;

    /**
     * Restore deleted opportunity
     */
    public function restore(string $id): OpportunityInterface;
}
