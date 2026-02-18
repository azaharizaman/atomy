<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface CustomerInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getCode(): ?string;

    public function getCreditLimit(): float;

    public function getAvailableCredit(): float;

    public function getPaymentTerms(): string;

    public function getCurrencyCode(): string;

    public function getPricingGroupId(): ?string;

    public function getSalespersonId(): ?string;

    public function isActive(): bool;
}
