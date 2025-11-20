<?php

declare(strict_types=1);

namespace Nexus\Party\Contracts;

use Nexus\Party\Enums\AddressType;

/**
 * Repository interface for party address persistence operations.
 */
interface AddressRepositoryInterface
{
    /**
     * Find address by ID.
     *
     * @param string $id Address ULID
     * @return AddressInterface|null
     */
    public function findById(string $id): ?AddressInterface;
    
    /**
     * Get all addresses for a party.
     *
     * @param string $partyId Party ULID
     * @return array<AddressInterface>
     */
    public function getByPartyId(string $partyId): array;
    
    /**
     * Get primary address for a party by type.
     *
     * @param string $partyId Party ULID
     * @param AddressType|null $type Address type filter
     * @return AddressInterface|null
     */
    public function getPrimaryAddress(string $partyId, ?AddressType $type = null): ?AddressInterface;
    
    /**
     * Get active addresses for a party.
     *
     * @param string $partyId Party ULID
     * @param \DateTimeInterface|null $asOf Date to check (defaults to now)
     * @return array<AddressInterface>
     */
    public function getActiveAddresses(string $partyId, ?\DateTimeInterface $asOf = null): array;
    
    /**
     * Save address.
     *
     * @param array<string, mixed> $data Address data
     * @return AddressInterface
     */
    public function save(array $data): AddressInterface;
    
    /**
     * Update address.
     *
     * @param string $id Address ULID
     * @param array<string, mixed> $data Updated data
     * @return AddressInterface
     */
    public function update(string $id, array $data): AddressInterface;
    
    /**
     * Delete address.
     *
     * @param string $id Address ULID
     * @return bool
     */
    public function delete(string $id): bool;
    
    /**
     * Clear primary flag for all addresses of a specific type for a party.
     *
     * @param string $partyId Party ULID
     * @param AddressType $type Address type
     * @return void
     */
    public function clearPrimaryFlag(string $partyId, AddressType $type): void;
}
