<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Payment\Enums\PaymentMethodType;

/**
 * Query interface for payment methods (CQRS - Read).
 */
interface PaymentMethodQueryInterface
{
    /**
     * Find a payment method by its ID.
     */
    public function findById(string $id): ?PaymentMethodInterface;

    /**
     * Find payment methods by party ID.
     *
     * @return array<PaymentMethodInterface>
     */
    public function findByPartyId(string $partyId): array;

    /**
     * Find payment methods by party ID and type.
     *
     * @return array<PaymentMethodInterface>
     */
    public function findByPartyIdAndType(string $partyId, PaymentMethodType $type): array;

    /**
     * Find the default payment method for a party.
     */
    public function findDefaultForParty(string $partyId): ?PaymentMethodInterface;

    /**
     * Find the default payment method for a party and type.
     */
    public function findDefaultForPartyAndType(
        string $partyId,
        PaymentMethodType $type,
    ): ?PaymentMethodInterface;

    /**
     * Find a payment method by fingerprint (for duplicate detection).
     */
    public function findByFingerprint(string $fingerprint): ?PaymentMethodInterface;

    /**
     * Find active payment methods by party ID.
     *
     * @return array<PaymentMethodInterface>
     */
    public function findActiveByPartyId(string $partyId): array;

    /**
     * Find expiring payment methods.
     *
     * @return array<PaymentMethodInterface>
     */
    public function findExpiringSoon(\DateTimeImmutable $before): array;

    /**
     * Count payment methods by party ID.
     */
    public function countByPartyId(string $partyId): int;
}
