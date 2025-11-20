<?php

declare(strict_types=1);

namespace Nexus\Connector\Services;

use Nexus\Connector\Contracts\WebhookVerifierInterface;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;

/**
 * Default webhook signature verifier using HMAC.
 * 
 * Supports dual code paths:
 * - Legacy mode: Direct hash_hmac() calls
 * - Crypto mode: Nexus\Crypto interfaces (CRYPTO_LEGACY_MODE=false)
 */
final class WebhookVerifier implements WebhookVerifierInterface
{
    /**
     * @param AsymmetricSignerInterface|null $signer Optional Nexus\Crypto signer (injected when available)
     * @param bool $legacyMode Whether to use legacy cryptography (default: true for safety)
     */
    public function __construct(
        private readonly ?AsymmetricSignerInterface $signer = null,
        private readonly bool $legacyMode = true,
    ) {}
    
    /**
     * Verify webhook signature using HMAC comparison.
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        // Check if legacy mode is enabled
        if ($this->isLegacyMode()) {
            return $this->verifyLegacy($payload, $signature, $secret);
        }
        
        // Use Nexus\Crypto implementation
        return $this->verifyCrypto($payload, $signature, $secret);
    }

    /**
     * Generate HMAC signature for a payload.
     */
    public function generateSignature(string $payload, string $secret, string $algorithm = 'sha256'): string
    {
        // Check if legacy mode is enabled
        if ($this->isLegacyMode()) {
            return hash_hmac($algorithm, $payload, $secret);
        }
        
        // Use Nexus\Crypto implementation
        return $this->signer?->hmac($payload, $secret) ?? hash_hmac($algorithm, $payload, $secret);
    }
    
    /**
     * Legacy verification implementation
     */
    private function verifyLegacy(string $payload, string $signature, string $secret): bool
    {
        // Remove common signature prefixes (e.g., "sha256=")
        $signature = preg_replace('/^(sha256|sha1)=/', '', $signature) ?? $signature;

        // Try multiple algorithms
        $algorithms = ['sha256', 'sha1'];
        
        foreach ($algorithms as $algorithm) {
            $expectedSignature = hash_hmac($algorithm, $payload, $secret);
            
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Crypto implementation using Nexus\Crypto
     */
    private function verifyCrypto(string $payload, string $signature, string $secret): bool
    {
        if ($this->signer === null) {
            // Fallback to legacy if signer not available
            return $this->verifyLegacy($payload, $signature, $secret);
        }
        
        // Remove common signature prefixes
        $signature = preg_replace('/^(sha256|sha1)=/', '', $signature) ?? $signature;
        
        // Verify using Nexus\Crypto
        return $this->signer->verifyHmac($payload, $signature, $secret, AsymmetricAlgorithm::HMACSHA256);
    }
    
    /**
     * Check if legacy mode is enabled
     */
    private function isLegacyMode(): bool
    {
        return $this->legacyMode;
    }
}

