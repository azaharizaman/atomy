<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface CreditManagerInterface
{
    public function checkCreditLimit(string $tenantId, string $customerId, float $amount): CreditCheckResultInterface;

    public function getAvailableCredit(string $tenantId, string $customerId): float;

    public function getCreditLimit(string $tenantId, string $customerId): float;

    public function getCreditUsed(string $tenantId, string $customerId): float;

    public function reserveCredit(string $tenantId, string $customerId, string $orderId, float $amount): bool;

    public function releaseCredit(string $tenantId, string $orderId): void;

    public function convertReservationToUsed(string $tenantId, string $orderId): void;

    public function isOnCreditHold(string $tenantId, string $customerId): bool;

    public function getCreditHoldReason(string $tenantId, string $customerId): ?string;

    public function placeOnCreditHold(string $tenantId, string $customerId, string $reason): void;

    public function releaseCreditHold(string $tenantId, string $customerId): void;
}

interface CreditCheckResultInterface
{
    public function isApproved(): bool;

    public function getAvailableCredit(): float;

    public function getRequestedAmount(): float;

    public function getCreditLimit(): float;

    public function getCurrentUsage(): float;

    public function getReason(): ?string;
}
