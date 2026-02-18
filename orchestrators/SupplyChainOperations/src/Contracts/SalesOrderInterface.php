<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface SalesOrderInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getCustomerId(): string;

    public function getOrderNumber(): string;

    public function getCurrencyCode(): string;

    public function getShippingAddress(): ?string;

    public function getConfirmedBy(): ?string;

    public function getLines(): array;
}
