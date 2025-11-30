<?php

declare(strict_types=1);

namespace Nexus\Party\Services;

use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Party\Contracts\AddressRepositoryInterface;
use Nexus\Party\Contracts\ContactMethodRepositoryInterface;
use Nexus\Party\Contracts\PartyInterface;
use Nexus\Party\Contracts\AddressInterface;
use Nexus\Party\Contracts\ContactMethodInterface;
use Nexus\Party\Enums\PartyType;
use Nexus\Party\Enums\AddressType;
use Nexus\Party\Enums\ContactMethodType;
use Nexus\Party\ValueObjects\TaxIdentity;
use Nexus\Party\ValueObjects\PostalAddress;
use Nexus\Party\Exceptions\PartyNotFoundException;
use Nexus\Party\Exceptions\DuplicatePartyException;
use Nexus\Party\Exceptions\ContactMethodException;
use Psr\Log\LoggerInterface;

/**
 * Party management service.
 * 
 * Provides business logic for creating, updating, and managing parties
 * (individuals and organizations) and their associated data.
 */
final class PartyManager
{
    public function __construct(
        private readonly PartyRepositoryInterface $partyRepository,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly ContactMethodRepositoryInterface $contactMethodRepository,
        private readonly LoggerInterface $logger
    ) {}
    
    /**
     * Create a new organization party.
     *
     * @param string $tenantId Tenant ULID
     * @param string $legalName Legal registered name
     * @param string|null $tradingName Trading/brand name (DBA)
     * @param TaxIdentity|null $taxIdentity Tax identification
     * @param \DateTimeInterface|null $registrationDate Date of incorporation
     * @param array<string, mixed> $metadata Additional metadata
     * @return PartyInterface
     * @throws DuplicatePartyException
     */
    public function createOrganization(
        string $tenantId,
        string $legalName,
        ?string $tradingName = null,
        ?TaxIdentity $taxIdentity = null,
        ?\DateTimeInterface $registrationDate = null,
        array $metadata = []
    ): PartyInterface {
        // Check for duplicates
        if ($this->partyRepository->findByLegalName($tenantId, $legalName)) {
            throw DuplicatePartyException::forLegalName($legalName);
        }
        
        if ($taxIdentity && $this->partyRepository->findByTaxIdentity(
            $tenantId,
            $taxIdentity->country,
            $taxIdentity->number
        )) {
            throw DuplicatePartyException::forTaxIdentity($taxIdentity->country, $taxIdentity->number);
        }
        
        $data = [
            'tenant_id' => $tenantId,
            'party_type' => PartyType::ORGANIZATION->value,
            'legal_name' => $legalName,
            'trading_name' => $tradingName,
            'tax_identity' => $taxIdentity?->toArray(),
            'registration_date' => $registrationDate,
            'metadata' => $metadata,
        ];
        
        $party = $this->partyRepository->save($data);
        
        $this->logger->info("Organization party created", [
            'party_id' => $party->getId(),
            'legal_name' => $legalName,
            'tenant_id' => $tenantId,
        ]);
        
        return $party;
    }
    
    /**
     * Create a new individual party.
     *
     * @param string $tenantId Tenant ULID
     * @param string $fullName Full legal name
     * @param \DateTimeInterface|null $dateOfBirth Date of birth
     * @param string|null $preferredName Preferred/nickname
     * @param TaxIdentity|null $taxIdentity Tax identification (SSN, etc.)
     * @param array<string, mixed> $metadata Additional metadata
     * @return PartyInterface
     */
    public function createIndividual(
        string $tenantId,
        string $fullName,
        ?\DateTimeInterface $dateOfBirth = null,
        ?string $preferredName = null,
        ?TaxIdentity $taxIdentity = null,
        array $metadata = []
    ): PartyInterface {
        $data = [
            'tenant_id' => $tenantId,
            'party_type' => PartyType::INDIVIDUAL->value,
            'legal_name' => $fullName,
            'trading_name' => $preferredName,
            'date_of_birth' => $dateOfBirth,
            'tax_identity' => $taxIdentity?->toArray(),
            'metadata' => $metadata,
        ];
        
        $party = $this->partyRepository->save($data);
        
        $this->logger->info("Individual party created", [
            'party_id' => $party->getId(),
            'full_name' => $fullName,
            'tenant_id' => $tenantId,
        ]);
        
        return $party;
    }
    
    /**
     * Update party details.
     *
     * @param string $partyId Party ULID
     * @param array<string, mixed> $data Updated data
     * @return PartyInterface
     * @throws PartyNotFoundException
     */
    public function updateParty(string $partyId, array $data): PartyInterface
    {
        $party = $this->partyRepository->findById($partyId);
        if (!$party) {
            throw PartyNotFoundException::forId($partyId);
        }
        
        $updatedParty = $this->partyRepository->update($partyId, $data);
        
        $this->logger->info("Party updated", [
            'party_id' => $partyId,
            'changes' => array_keys($data),
        ]);
        
        return $updatedParty;
    }
    
    /**
     * Get party by ID.
     *
     * @param string $partyId Party ULID
     * @return PartyInterface
     * @throws PartyNotFoundException
     */
    public function getParty(string $partyId): PartyInterface
    {
        $party = $this->partyRepository->findById($partyId);
        if (!$party) {
            throw PartyNotFoundException::forId($partyId);
        }
        
        return $party;
    }
    
    /**
     * Add an address to a party.
     *
     * @param string $partyId Party ULID
     * @param AddressType $type Address type
     * @param PostalAddress $address Postal address value object
     * @param bool $isPrimary Mark as primary address for this type
     * @param \DateTimeInterface|null $effectiveFrom Effective start date
     * @param array<string, mixed> $metadata Additional metadata
     * @return AddressInterface
     * @throws PartyNotFoundException
     */
    public function addAddress(
        string $partyId,
        AddressType $type,
        PostalAddress $address,
        bool $isPrimary = false,
        ?\DateTimeInterface $effectiveFrom = null,
        array $metadata = []
    ): AddressInterface {
        // Verify party exists
        $party = $this->getParty($partyId);
        
        // If marking as primary, clear existing primary flag for this type
        if ($isPrimary) {
            $this->addressRepository->clearPrimaryFlag($partyId, $type);
        }
        
        $data = [
            'party_id' => $partyId,
            'address_type' => $type->value,
            'postal_address' => $address->toArray(),
            'is_primary' => $isPrimary,
            'effective_from' => $effectiveFrom ?? new \DateTimeImmutable(),
            'metadata' => $metadata,
        ];
        
        $savedAddress = $this->addressRepository->save($data);
        
        $this->logger->info("Address added to party", [
            'party_id' => $partyId,
            'address_id' => $savedAddress->getId(),
            'address_type' => $type->value,
            'is_primary' => $isPrimary,
        ]);
        
        return $savedAddress;
    }
    
    /**
     * Update an existing address.
     *
     * @param string $addressId Address ULID
     * @param array<string, mixed> $data Updated data
     * @return AddressInterface
     */
    public function updateAddress(string $addressId, array $data): AddressInterface
    {
        return $this->addressRepository->update($addressId, $data);
    }
    
    /**
     * Set an address as primary for its type.
     *
     * @param string $addressId Address ULID
     * @return AddressInterface
     */
    public function setPrimaryAddress(string $addressId): AddressInterface
    {
        $address = $this->addressRepository->findById($addressId);
        if (!$address) {
            throw new \RuntimeException("Address not found: {$addressId}");
        }
        
        // Clear existing primary flag for this type
        $this->addressRepository->clearPrimaryFlag(
            $address->getPartyId(),
            $address->getAddressType()
        );
        
        // Set this address as primary
        return $this->addressRepository->update($addressId, ['is_primary' => true]);
    }
    
    /**
     * Add a contact method to a party.
     *
     * @param string $partyId Party ULID
     * @param ContactMethodType $type Contact method type
     * @param string $value Contact value (email, phone, etc.)
     * @param bool $isPrimary Mark as primary for this type
     * @param bool $isVerified Mark as verified
     * @param array<string, mixed> $metadata Additional metadata
     * @return ContactMethodInterface
     * @throws PartyNotFoundException
     * @throws ContactMethodException
     */
    public function addContactMethod(
        string $partyId,
        ContactMethodType $type,
        string $value,
        bool $isPrimary = false,
        bool $isVerified = false,
        array $metadata = []
    ): ContactMethodInterface {
        // Verify party exists
        $party = $this->getParty($partyId);
        
        // Validate format if required
        if ($type->requiresValidation()) {
            $pattern = $type->getValidationPattern();
            if ($pattern && !preg_match($pattern, $value)) {
                throw ContactMethodException::invalidFormat($type->value, $value);
            }
        }
        
        // If marking as primary, clear existing primary flag for this type
        if ($isPrimary) {
            $this->contactMethodRepository->clearPrimaryFlag($partyId, $type);
        }
        
        $data = [
            'party_id' => $partyId,
            'type' => $type->value,
            'value' => $value,
            'is_primary' => $isPrimary,
            'is_verified' => $isVerified,
            'verified_at' => $isVerified ? new \DateTimeImmutable() : null,
            'metadata' => $metadata,
        ];
        
        $contactMethod = $this->contactMethodRepository->save($data);
        
        $this->logger->info("Contact method added to party", [
            'party_id' => $partyId,
            'contact_method_id' => $contactMethod->getId(),
            'type' => $type->value,
            'is_primary' => $isPrimary,
        ]);
        
        return $contactMethod;
    }
    
    /**
     * Update a contact method.
     *
     * @param string $contactMethodId Contact method ULID
     * @param array<string, mixed> $data Updated data
     * @return ContactMethodInterface
     */
    public function updateContactMethod(string $contactMethodId, array $data): ContactMethodInterface
    {
        return $this->contactMethodRepository->update($contactMethodId, $data);
    }
    
    /**
     * Set a contact method as primary for its type.
     *
     * @param string $contactMethodId Contact method ULID
     * @return ContactMethodInterface
     */
    public function setPrimaryContactMethod(string $contactMethodId): ContactMethodInterface
    {
        $contactMethod = $this->contactMethodRepository->findById($contactMethodId);
        if (!$contactMethod) {
            throw new \RuntimeException("Contact method not found: {$contactMethodId}");
        }
        
        // Clear existing primary flag for this type
        $this->contactMethodRepository->clearPrimaryFlag(
            $contactMethod->getPartyId(),
            $contactMethod->getType()
        );
        
        // Set this contact method as primary
        return $this->contactMethodRepository->update($contactMethodId, ['is_primary' => true]);
    }
    
    /**
     * Get primary email address for a party.
     *
     * @param string $partyId Party ULID
     * @return string|null
     */
    public function getPrimaryEmail(string $partyId): ?string
    {
        $emailContact = $this->contactMethodRepository->getPrimaryContactMethod(
            $partyId,
            ContactMethodType::EMAIL
        );
        
        return $emailContact?->getValue();
    }
    
    /**
     * Get primary phone number for a party.
     *
     * @param string $partyId Party ULID
     * @return string|null
     */
    public function getPrimaryPhone(string $partyId): ?string
    {
        $phoneContact = $this->contactMethodRepository->getPrimaryContactMethod(
            $partyId,
            ContactMethodType::PHONE
        );
        
        if (!$phoneContact) {
            $phoneContact = $this->contactMethodRepository->getPrimaryContactMethod(
                $partyId,
                ContactMethodType::MOBILE
            );
        }
        
        return $phoneContact?->getValue();
    }
    
    /**
     * Search for potential duplicate parties.
     *
     * @param string $tenantId Tenant ULID
     * @param string $name Name to search for
     * @param TaxIdentity|null $taxIdentity Tax identity to check
     * @return array<PartyInterface>
     */
    public function findPotentialDuplicates(
        string $tenantId,
        string $name,
        ?TaxIdentity $taxIdentity = null
    ): array {
        $duplicates = [];
        
        // Search by name (fuzzy)
        $nameMatches = $this->partyRepository->searchByName($tenantId, $name, 10);
        foreach ($nameMatches as $match) {
            $duplicates[$match->getId()] = $match;
        }
        
        // Check exact tax identity match
        if ($taxIdentity) {
            $taxMatch = $this->partyRepository->findByTaxIdentity(
                $tenantId,
                $taxIdentity->country,
                $taxIdentity->number
            );
            if ($taxMatch) {
                $duplicates[$taxMatch->getId()] = $taxMatch;
            }
        }
        
        return array_values($duplicates);
    }
    
    /**
     * List all parties for a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param array<string, mixed> $filters Optional filters
     * @return array<PartyInterface>
     */
    public function listParties(string $tenantId, array $filters = []): array
    {
        return $this->partyRepository->getAll($tenantId, $filters);
    }
}
