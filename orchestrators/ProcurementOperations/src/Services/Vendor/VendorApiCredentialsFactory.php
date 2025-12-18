<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Vendor;

use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorApiCredentials;
use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * Factory service for creating vendor API credentials.
 *
 * Uses SecureIdGenerator (backed by Nexus\Crypto) for cryptographically
 * secure credential generation, replacing the static factory methods
 * in VendorApiCredentials that used raw random_bytes().
 *
 * Benefits:
 * - Post-quantum cryptography (PQC) readiness
 * - Algorithm agility (can switch algorithms without code changes)
 * - Centralized cryptographic operations
 * - Better testability (dependency injection)
 */
final readonly class VendorApiCredentialsFactory
{
    public function __construct(
        private SecureIdGeneratorInterface $idGenerator,
    ) {}

    /**
     * Create new credentials for vendor.
     *
     * @param array<string> $scopes
     */
    public function create(
        string $vendorId,
        VendorPortalTier $tier,
        array $scopes = ['read'],
        ?int $expiresInDays = null,
    ): VendorApiCredentials {
        $clientId = $this->generateClientId($vendorId);
        $clientSecret = $this->generateClientSecret();

        $rateLimit = $tier->getApiRateLimit();
        $dailyQuota = $rateLimit * 60 * 24; // Rate limit * minutes * hours

        return new VendorApiCredentials(
            vendorId: $vendorId,
            clientId: $clientId,
            clientSecret: $clientSecret,
            tier: $tier,
            status: 'active',
            allowedScopes: $scopes,
            allowedIpAddresses: [],
            rateLimit: $rateLimit,
            dailyQuota: $dailyQuota,
            usedToday: 0,
            createdAt: new \DateTimeImmutable(),
            lastUsedAt: null,
            expiresAt: $expiresInDays !== null
                ? new \DateTimeImmutable("+{$expiresInDays} days")
                : null,
        );
    }

    /**
     * Create read-only credentials.
     */
    public function createReadOnly(string $vendorId, VendorPortalTier $tier): VendorApiCredentials
    {
        return $this->create(
            vendorId: $vendorId,
            tier: $tier,
            scopes: ['read', 'catalog'],
        );
    }

    /**
     * Create full-access credentials.
     */
    public function createFullAccess(string $vendorId, VendorPortalTier $tier): VendorApiCredentials
    {
        return $this->create(
            vendorId: $vendorId,
            tier: $tier,
            scopes: ['read', 'write', 'catalog', 'orders', 'invoices', 'inventory'],
        );
    }

    /**
     * Regenerate client secret for existing credentials.
     */
    public function regenerateSecret(VendorApiCredentials $credentials): VendorApiCredentials
    {
        $newSecret = $this->generateClientSecret();

        return new VendorApiCredentials(
            vendorId: $credentials->vendorId,
            clientId: $credentials->clientId,
            clientSecret: $newSecret,
            tier: $credentials->tier,
            status: $credentials->status,
            allowedScopes: $credentials->allowedScopes,
            allowedIpAddresses: $credentials->allowedIpAddresses,
            rateLimit: $credentials->rateLimit,
            dailyQuota: $credentials->dailyQuota,
            usedToday: $credentials->usedToday,
            createdAt: $credentials->createdAt,
            lastUsedAt: $credentials->lastUsedAt,
            expiresAt: $credentials->expiresAt,
            metadata: array_merge($credentials->metadata, [
                'secret_regenerated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]),
        );
    }

    /**
     * Generate a secure client ID using Crypto package.
     *
     * Uses HMAC-based derivation for deterministic but secure IDs.
     */
    private function generateClientId(string $vendorId): string
    {
        return $this->idGenerator->generateClientId($vendorId);
    }

    /**
     * Generate a secure client secret using Crypto package.
     *
     * Uses cryptographically secure random bytes.
     */
    private function generateClientSecret(): string
    {
        return $this->idGenerator->generateClientSecret(32);
    }
}
