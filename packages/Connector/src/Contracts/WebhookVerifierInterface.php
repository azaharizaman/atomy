<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

/**
 * Interface for verifying webhook signatures.
 *
 * Ensures incoming webhook requests are authentic and from the expected source.
 */
interface WebhookVerifierInterface
{
    /**
     * Verify webhook signature.
     *
     * @param string $payload Raw webhook payload body
     * @param string $signature Signature from webhook headers
     * @param string $secret Webhook signing secret
     * @return bool True if signature is valid
     */
    public function verify(string $payload, string $signature, string $secret): bool;

    /**
     * Generate HMAC signature for testing.
     *
     * @param string $payload Raw payload body
     * @param string $secret Signing secret
     * @param string $algorithm Hashing algorithm (default: sha256)
     * @return string Generated signature
     */
    public function generateSignature(string $payload, string $secret, string $algorithm = 'sha256'): string;
}
