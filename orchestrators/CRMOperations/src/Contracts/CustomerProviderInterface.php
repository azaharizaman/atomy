<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

/**
 * Customer Provider Interface
 * 
 * Provides access to customer data from the Party package.
 * This interface abstracts the Party package integration.
 * 
 * @package Nexus\CRMOperations\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface CustomerProviderInterface
{
    /**
     * Find customer by ID
     * 
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array;

    /**
     * Find customer by email
     * 
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array;

    /**
     * Create a new customer from lead data
     * 
     * @param array<string, mixed> $leadData
     * @return string The created customer ID
     */
    public function createFromLead(array $leadData): string;

    /**
     * Update customer information
     * 
     * @param array<string, mixed> $data
     */
    public function update(string $customerId, array $data): void;

    /**
     * Check if customer exists
     */
    public function exists(string $customerId): bool;

    /**
     * Link customer to opportunity
     */
    public function linkToOpportunity(string $customerId, string $opportunityId): void;

    /**
     * Get customer's communication preferences
     * 
     * @return array<string, mixed>
     */
    public function getCommunicationPreferences(string $customerId): array;
}
