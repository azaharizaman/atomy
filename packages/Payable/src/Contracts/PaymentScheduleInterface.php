<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Payment schedule entity interface.
 */
interface PaymentScheduleInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getBillId(): string;
    public function getVendorId(): string;
    public function getScheduledAmount(): float;
    public function getDueDate(): \DateTimeInterface;
    public function getEarlyPaymentDiscountPercent(): float;
    public function getEarlyPaymentDiscountDate(): ?\DateTimeInterface;
    public function getStatus(): string;
    public function getPaymentId(): ?string;
    public function getGlJournalId(): ?string;
    public function getCurrency(): string;
    public function getCreatedAt(): \DateTimeInterface;
    public function getUpdatedAt(): \DateTimeInterface;
}
