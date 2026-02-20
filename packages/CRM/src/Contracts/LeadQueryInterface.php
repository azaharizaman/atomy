<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\LeadStatus;
use Nexus\CRM\Enums\LeadSource;

/**
 * Lead Query Interface
 * 
 * Provides read-only query operations for leads.
 * Implements CQRS query separation pattern.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface LeadQueryInterface
{
    /**
     * Find lead by ID
     */
    public function findById(string $id): ?LeadInterface;

    /**
     * Find lead by ID or throw exception
     * 
     * @throws \Nexus\CRM\Exceptions\LeadNotFoundException
     */
    public function findByIdOrFail(string $id): LeadInterface;

    /**
     * Find lead by external reference
     */
    public function findByExternalRef(string $externalRef): ?LeadInterface;

    /**
     * Find leads by status
     * 
     * @return iterable<LeadInterface>
     */
    public function findByStatus(LeadStatus $status): iterable;

    /**
     * Find leads by source
     * 
     * @return iterable<LeadInterface>
     */
    public function findBySource(LeadSource $source): iterable;

    /**
     * Find leads created within date range
     * 
     * @return iterable<LeadInterface>
     */
    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): iterable;

    /**
     * Find leads with score above threshold
     * 
     * @return iterable<LeadInterface>
     */
    public function findHighScoring(int $minimumScore): iterable;

    /**
     * Find unassigned leads
     * 
     * @return iterable<LeadInterface>
     */
    public function findUnassigned(): iterable;

    /**
     * Find leads ready for conversion
     * 
     * @return iterable<LeadInterface>
     */
    public function findConvertible(): iterable;

    /**
     * Count leads by status
     */
    public function countByStatus(LeadStatus $status): int;

    /**
     * Count leads by source
     */
    public function countBySource(LeadSource $source): int;
}
