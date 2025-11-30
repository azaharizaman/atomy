<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Payment entity interface.
 */
interface PaymentInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getPaymentNumber(): string;
    public function getPaymentDate(): \DateTimeInterface;
    public function getAmount(): float;
    public function getCurrency(): string;
    public function getExchangeRate(): float;
    public function getPaymentMethod(): string;
    public function getBankAccount(): string;
    public function getReference(): string;
    public function getStatus(): string;
    public function getGlJournalId(): ?string;
    
    /**
     * Get bill allocations.
     *
     * @return array Array of ['bill_id' => string, 'amount' => float]
     */
    public function getAllocations(): array;
    
    public function getCreatedAt(): \DateTimeInterface;
    public function getUpdatedAt(): \DateTimeInterface;
}
