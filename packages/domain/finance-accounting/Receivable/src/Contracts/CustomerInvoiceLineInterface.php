<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

/**
 * Customer Invoice Line Interface
 *
 * Represents a single line item on a customer invoice.
 */
interface CustomerInvoiceLineInterface
{
    public function getId(): string;

    public function getInvoiceId(): string;

    public function getLineNumber(): int;

    public function getDescription(): string;

    public function getQuantity(): float;

    public function getUnitPrice(): float;

    public function getLineAmount(): float;

    /**
     * Get the GL revenue account code for this line
     * (e.g., "4100" for Sales Revenue)
     */
    public function getGlAccount(): string;

    public function getTaxCode(): ?string;

    public function getSalesOrderLineReference(): ?string;

    public function getProductId(): ?string;
}
