<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\LeadStatus;
use Nexus\CRM\Enums\LeadSource;

/**
 * Lead Persist Interface
 * 
 * Provides write operations for leads.
 * Implements CQRS command separation pattern.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface LeadPersistInterface
{
    /**
     * Create a new lead
     */
    public function create(
        string $tenantId,
        string $title,
        LeadSource $source,
        ?string $description = null,
        ?int $estimatedValue = null,
        ?string $currency = null,
        ?string $externalRef = null
    ): LeadInterface;

    /**
     * Update lead details
     */
    public function update(
        string $id,
        ?string $title = null,
        ?string $description = null,
        ?int $estimatedValue = null,
        ?string $currency = null
    ): LeadInterface;

    /**
     * Update lead status
     * 
     * @throws \Nexus\CRM\Exceptions\InvalidLeadStatusTransitionException
     */
    public function updateStatus(string $id, LeadStatus $status): LeadInterface;

    /**
     * Update lead source
     */
    public function updateSource(string $id, LeadSource $source): LeadInterface;

    /**
     * Assign lead score
     */
    public function assignScore(string $id, int $score, array $factors = []): LeadInterface;

    /**
     * Convert lead to opportunity
     * 
     * @return string The ID of the created opportunity
     * @throws \Nexus\CRM\Exceptions\LeadNotConvertibleException
     */
    public function convertToOpportunity(string $id): string;

    /**
     * Disqualify lead with reason
     */
    public function disqualify(string $id, string $reason): LeadInterface;

    /**
     * Delete lead (soft delete)
     */
    public function delete(string $id): void;

    /**
     * Restore deleted lead
     */
    public function restore(string $id): LeadInterface;
}
