<?php

declare(strict_types=1);

namespace Nexus\Vendor\ValueObjects;

use Nexus\Vendor\Contracts\VendorProfileInterface;

/**
 * Value object representing a vendor profile.
 */
final readonly class VendorProfileVO implements VendorProfileInterface
{
    public function __construct(
        private string $id,
        private string $name,
        private ?string $email = null,
        private ?string $phone = null,
        private ?string $address = null,
        private ?\DateTimeImmutable $createdAt = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
