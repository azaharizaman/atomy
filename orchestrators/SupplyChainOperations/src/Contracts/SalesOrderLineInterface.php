<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface SalesOrderLineInterface
{
    public function getId(): string;

    public function getProductVariantId(): string;

    public function getQuantity(): float;
}
