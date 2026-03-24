<?php
declare(strict_types=1);

namespace Nexus\Vendor\Contracts;

/**
 * Interface for vendor profile representations.
 */
interface VendorProfileInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getEmail(): ?string;

    public function getPhone(): ?string;

    public function getAddress(): ?string;

    public function getCreatedAt(): ?\DateTimeImmutable;
}
