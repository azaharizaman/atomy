<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

/**
 * Persist interface for payment methods (CQRS - Write).
 */
interface PaymentMethodPersistInterface
{
    /**
     * Save a payment method (create or update).
     */
    public function save(PaymentMethodInterface $method): PaymentMethodInterface;

    /**
     * Delete a payment method.
     */
    public function delete(string $id): void;

    /**
     * Deactivate a payment method.
     */
    public function deactivate(string $id): void;

    /**
     * Set a payment method as the default for its party and type.
     */
    public function setAsDefault(string $id): void;

    /**
     * Update the last used timestamp for a payment method.
     */
    public function updateLastUsed(string $id, \DateTimeImmutable $usedAt): void;

    /**
     * Mark a payment method as verified.
     */
    public function markAsVerified(string $id): void;
}
