<?php

declare(strict_types=1);

namespace Nexus\Party\Contracts;

use Nexus\Party\Enums\PartyType;

/**
 * Repository interface for party persistence operations.
 */
interface PartyRepositoryInterface
{
    /**
     * Find party by ID.
     *
     * @param string $id Party ULID
     * @return PartyInterface|null
     */
    public function findById(string $id): ?PartyInterface;
    
    /**
     * Find party by legal name.
     *
     * @param string $tenantId Tenant ULID
     * @param string $legalName Legal name
     * @return PartyInterface|null
     */
    public function findByLegalName(string $tenantId, string $legalName): ?PartyInterface;
    
    /**
     * Find party by tax identity.
     *
     * @param string $tenantId Tenant ULID
     * @param string $country Country code
     * @param string $taxNumber Tax identification number
     * @return PartyInterface|null
     */
    public function findByTaxIdentity(string $tenantId, string $country, string $taxNumber): ?PartyInterface;
    
    /**
     * Get all parties for a tenant with optional filters.
     *
     * @param string $tenantId Tenant ULID
     * @param array<string, mixed> $filters Optional filters (party_type, search)
     * @return array<PartyInterface>
     */
    public function getAll(string $tenantId, array $filters = []): array;
    
    /**
     * Get parties by type.
     *
     * @param string $tenantId Tenant ULID
     * @param PartyType $type Party type
     * @return array<PartyInterface>
     */
    public function getByType(string $tenantId, PartyType $type): array;
    
    /**
     * Search parties by name (fuzzy match).
     *
     * @param string $tenantId Tenant ULID
     * @param string $searchTerm Search term
     * @param int $limit Maximum results
     * @return array<PartyInterface>
     */
    public function searchByName(string $tenantId, string $searchTerm, int $limit = 50): array;
    
    /**
     * Save party (create or update).
     *
     * @param array<string, mixed> $data Party data
     * @return PartyInterface
     */
    public function save(array $data): PartyInterface;
    
    /**
     * Update party.
     *
     * @param string $id Party ULID
     * @param array<string, mixed> $data Updated data
     * @return PartyInterface
     */
    public function update(string $id, array $data): PartyInterface;
    
    /**
     * Delete party.
     *
     * @param string $id Party ULID
     * @return bool
     */
    public function delete(string $id): bool;
}
