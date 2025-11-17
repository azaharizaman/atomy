<?php

declare(strict_types=1);

namespace Nexus\Connector\Services;

use Nexus\Connector\Contracts\WebhookVerifierInterface;

/**
 * Default webhook signature verifier using HMAC.
 */
final class WebhookVerifier implements WebhookVerifierInterface
{
    /**
     * Verify webhook signature using HMAC comparison.
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        // Remove common signature prefixes (e.g., "sha256=")
        $signature = preg_replace('/^(sha256|sha1)=/', '', $signature) ?? $signature;

        // Try multiple algorithms
        $algorithms = ['sha256', 'sha1'];
        
        foreach ($algorithms as $algorithm) {
            $expectedSignature = $this->generateSignature($payload, $secret, $algorithm);
            
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate HMAC signature for a payload.
     */
    public function generateSignature(string $payload, string $secret, string $algorithm = 'sha256'): string
    {
        return hash_hmac($algorithm, $payload, $secret);
    }
}
