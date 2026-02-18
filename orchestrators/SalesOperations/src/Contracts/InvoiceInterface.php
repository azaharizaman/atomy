<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface InvoiceInterface
{
    public function getId(): string;

    public function getInvoiceNumber(): string;

    public function getOrderId(): string;

    public function getCustomerId(): string;

    public function getStatus(): string;

    public function getSubtotal(): float;

    public function getTaxAmount(): float;

    public function getTotal(): float;

    public function getBalanceDue(): float;

    public function getCurrencyCode(): string;

    public function getInvoiceDate(): \DateTimeImmutable;

    public function getDueDate(): \DateTimeImmutable;

    public function isPaid(): bool;

    public function isOverdue(): bool;
}

interface InvoiceProviderInterface
{
    public function findById(string $tenantId, string $invoiceId): ?InvoiceInterface;

    public function findByOrder(string $tenantId, string $orderId): ?InvoiceInterface;

    public function create(string $tenantId, array $data): InvoiceInterface;

    public function applyPayment(string $tenantId, string $invoiceId, float $amount): void;

    public function void(string $tenantId, string $invoiceId, string $reason): void;
}
