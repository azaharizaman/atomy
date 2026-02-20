<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

/**
 * Quotation Provider Interface
 * 
 * Provides access to quotation data from the Sales package.
 * This interface abstracts the Sales package integration.
 * 
 * @package Nexus\CRMOperations\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface QuotationProviderInterface
{
    /**
     * Find quotation by ID
     * 
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array;

    /**
     * Find quotation by opportunity ID
     * 
     * @return array<string, mixed>|null
     */
    public function findByOpportunityId(string $opportunityId): ?array;

    /**
     * Create quotation from opportunity data
     * 
     * @param array<string, mixed> $opportunityData
     * @return string The created quotation ID
     */
    public function createFromOpportunity(array $opportunityData): string;

    /**
     * Update quotation
     * 
     * @param array<string, mixed> $data
     */
    public function update(string $quotationId, array $data): void;

    /**
     * Get quotation status
     */
    public function getStatus(string $quotationId): string;

    /**
     * Mark quotation as sent
     */
    public function markAsSent(string $quotationId): void;

    /**
     * Mark quotation as accepted
     */
    public function markAsAccepted(string $quotationId): void;

    /**
     * Mark quotation as rejected
     */
    public function markAsRejected(string $quotationId, string $reason): void;

    /**
     * Get quotation total value
     */
    public function getTotalValue(string $quotationId): int;

    /**
     * Get quotation currency
     */
    public function getCurrency(string $quotationId): string;

    /**
     * Check if quotation is expired
     */
    public function isExpired(string $quotationId): bool;

    /**
     * Get quotation expiry date
     */
    public function getExpiryDate(string $quotationId): \DateTimeImmutable;
}
