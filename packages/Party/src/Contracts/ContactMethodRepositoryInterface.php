<?php

declare(strict_types=1);

namespace Nexus\Party\Contracts;

use Nexus\Party\Enums\ContactMethodType;

/**
 * Repository interface for party contact method persistence operations.
 */
interface ContactMethodRepositoryInterface
{
    /**
     * Find contact method by ID.
     *
     * @param string $id Contact method ULID
     * @return ContactMethodInterface|null
     */
    public function findById(string $id): ?ContactMethodInterface;
    
    /**
     * Get all contact methods for a party.
     *
     * @param string $partyId Party ULID
     * @return array<ContactMethodInterface>
     */
    public function getByPartyId(string $partyId): array;
    
    /**
     * Get contact methods by type for a party.
     *
     * @param string $partyId Party ULID
     * @param ContactMethodType $type Contact method type
     * @return array<ContactMethodInterface>
     */
    public function getByType(string $partyId, ContactMethodType $type): array;
    
    /**
     * Get primary contact method for a party by type.
     *
     * @param string $partyId Party ULID
     * @param ContactMethodType $type Contact method type
     * @return ContactMethodInterface|null
     */
    public function getPrimaryContactMethod(string $partyId, ContactMethodType $type): ?ContactMethodInterface;
    
    /**
     * Find contact method by value.
     *
     * @param string $tenantId Tenant ULID
     * @param ContactMethodType $type Contact method type
     * @param string $value Contact value (email, phone, etc.)
     * @return ContactMethodInterface|null
     */
    public function findByValue(string $tenantId, ContactMethodType $type, string $value): ?ContactMethodInterface;
    
    /**
     * Save contact method.
     *
     * @param array<string, mixed> $data Contact method data
     * @return ContactMethodInterface
     */
    public function save(array $data): ContactMethodInterface;
    
    /**
     * Update contact method.
     *
     * @param string $id Contact method ULID
     * @param array<string, mixed> $data Updated data
     * @return ContactMethodInterface
     */
    public function update(string $id, array $data): ContactMethodInterface;
    
    /**
     * Delete contact method.
     *
     * @param string $id Contact method ULID
     * @return bool
     */
    public function delete(string $id): bool;
    
    /**
     * Clear primary flag for all contact methods of a specific type for a party.
     *
     * @param string $partyId Party ULID
     * @param ContactMethodType $type Contact method type
     * @return void
     */
    public function clearPrimaryFlag(string $partyId, ContactMethodType $type): void;
}
