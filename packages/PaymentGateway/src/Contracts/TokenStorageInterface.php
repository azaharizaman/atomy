<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\ValueObjects\PaymentToken;

/**
 * Contract for secure token storage.
 *
 * Stores token references and metadata for reuse across transactions.
 * SECURITY: Only stores gateway-provided tokens, never raw card data.
 */
interface TokenStorageInterface
{
    /**
     * Store a token.
     *
     * @param string $tenantId Tenant identifier
     * @param string $customerId Customer identifier
     * @param PaymentToken $token Token to store
     * @return string Storage ID for the token
     */
    public function store(
        string $tenantId,
        string $customerId,
        PaymentToken $token,
    ): string;

    /**
     * Retrieve a token by storage ID.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\TokenNotFoundException
     */
    public function retrieve(string $storageId): PaymentToken;

    /**
     * Get all tokens for a customer.
     *
     * @return array<PaymentToken>
     */
    public function getCustomerTokens(string $tenantId, string $customerId): array;

    /**
     * Delete a token.
     */
    public function delete(string $storageId): void;

    /**
     * Delete all tokens for a customer.
     */
    public function deleteCustomerTokens(string $tenantId, string $customerId): void;

    /**
     * Check if a token exists.
     */
    public function exists(string $storageId): bool;

    /**
     * Set a token as the default for a customer.
     */
    public function setDefault(string $tenantId, string $customerId, string $storageId): void;

    /**
     * Get the default token for a customer.
     */
    public function getDefault(string $tenantId, string $customerId): ?PaymentToken;
}
