<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeInterface;

/**
 * Unapplied Cash Interface
 *
 * Represents customer prepayments or payments received before invoice creation.
 * Tracked as a liability until applied to an invoice.
 */
interface UnappliedCashInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getCustomerId(): string;

    public function getReceiptId(): string;

    public function getAmount(): float;

    public function getCurrency(): string;

    public function getReceivedDate(): DateTimeInterface;

    public function getGlJournalId(): ?string;

    public function getStatus(): string;

    /**
     * Check if this unapplied cash has been applied to an invoice
     */
    public function isApplied(): bool;

    public function getCreatedAt(): DateTimeInterface;

    public function getUpdatedAt(): DateTimeInterface;
}
