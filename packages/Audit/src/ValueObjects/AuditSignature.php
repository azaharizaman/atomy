<?php

declare(strict_types=1);

namespace Nexus\Audit\ValueObjects;

/**
 * Audit Signature Value Object
 * 
 * Immutable container for digital signature (Ed25519).
 * Used for non-repudiation in high-compliance environments.
 */
final readonly class AuditSignature
{
    public function __construct(
        public string $signature,
        public string $signedBy,
        public string $algorithm = 'Ed25519'
    ) {
        if (empty($signature)) {
            throw new \InvalidArgumentException('Signature cannot be empty');
        }

        if (empty($signedBy)) {
            throw new \InvalidArgumentException('Signer identifier cannot be empty');
        }

        if (!in_array($algorithm, ['Ed25519'], true)) {
            throw new \InvalidArgumentException("Unsupported signature algorithm: {$algorithm}");
        }
    }

    /**
     * Create Ed25519 signature
     */
    public static function ed25519(string $signature, string $signedBy): self
    {
        return new self($signature, $signedBy, 'Ed25519');
    }

    /**
     * Get signature as string
     */
    public function toString(): string
    {
        return $this->signature;
    }

    /**
     * Get signer identifier
     */
    public function getSignerId(): string
    {
        return $this->signedBy;
    }

    /**
     * Check if signature is Ed25519
     */
    public function isEd25519(): bool
    {
        return $this->algorithm === 'Ed25519';
    }

    /**
     * Get metadata array for storage
     */
    public function toArray(): array
    {
        return [
            'signature' => $this->signature,
            'signed_by' => $this->signedBy,
            'algorithm' => $this->algorithm,
        ];
    }

    public function __toString(): string
    {
        return $this->signature;
    }
}
