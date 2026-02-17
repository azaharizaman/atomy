<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Adapters;

use Nexus\AmlCompliance\Contracts\PartyInterface as AmlPartyInterface;
use Nexus\Payable\Contracts\VendorInterface;

/**
 * Adapter specifically for AmlCompliance risk assessment.
 */
class VendorToAmlPartyAdapter implements AmlPartyInterface
{
    public function __construct(
        private readonly VendorInterface $vendor,
    ) {}

    public function getId(): string
    {
        return $this->vendor->getId();
    }

    public function getName(): string
    {
        return $this->vendor->getName();
    }

    public function getType(): string
    {
        // For standard vendors, we assume they are organizations unless specified otherwise.
        return 'organization';
    }

    public function getCountryCode(): string
    {
        // Extract country from address if available, default to 'MY'
        return $this->vendor->getAddress()['country_code'] ?? 'MY';
    }

    public function getAssociatedCountryCodes(): array
    {
        return [$this->getCountryCode()];
    }

    public function getIndustryCode(): ?string
    {
        // Not directly available on VendorInterface, could be in metadata
        return null;
    }

    public function isPep(): bool
    {
        return false; // Organizations aren't PEPs
    }

    public function getPepLevel(): ?int
    {
        return null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        $dt = $this->vendor->getCreatedAt();
        return $dt instanceof \DateTimeImmutable ? $dt : \DateTimeImmutable::createFromInterface($dt);
    }

    public function getDateOfBirthOrIncorporation(): ?\DateTimeImmutable
    {
        return null;
    }

    public function getBeneficialOwners(): array
    {
        return [];
    }

    public function getIdentifiers(): array
    {
        $identifiers = [];
        if ($this->vendor->getTaxId()) {
            $identifiers['tax_id'] = $this->vendor->getTaxId();
        }
        return $identifiers;
    }

    public function getMetadata(): array
    {
        return [
            'payment_terms' => $this->vendor->getPaymentTerms(),
            'currency' => $this->vendor->getCurrency(),
        ];
    }

    public function isActive(): bool
    {
        return $this->vendor->getStatus() === 'active';
    }

    public function getLastActivityDate(): ?\DateTimeImmutable
    {
        $dt = $this->vendor->getUpdatedAt();
        return $dt instanceof \DateTimeImmutable ? $dt : \DateTimeImmutable::createFromInterface($dt);
    }
}
