<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\ValueObjects\EncryptedData;

/**
 * Hybrid KEM Interface (Phase 2 - PQC)
 *
 * Provides hybrid key encapsulation mechanism combining classical and post-quantum algorithms.
 * Establishes shared secret using dual KEM (e.g., X25519 + Kyber768) for quantum-resistant
 * key exchange.
 *
 * STATUS: Not yet implemented (Phase 2 feature).
 * Will throw FeatureNotImplementedException until Q3 2026.
 */
interface HybridKEMInterface
{
    /**
     * Encapsulate shared secret with hybrid KEM
     *
     * Generates two shared secrets:
     * 1. Classical (e.g., X25519 ECDH) for current security
     * 2. Post-quantum (e.g., Kyber768) for future security
     *
     * Shared secrets are combined using KDF to produce final key.
     *
     * @param string $classicalPublicKey Classical algorithm public key
     * @param string $pqcPublicKey Post-quantum algorithm public key
     * @return EncryptedData Encapsulated key (contains both ciphertexts)
     * @throws \Nexus\Crypto\Exceptions\FeatureNotImplementedException Phase 2 feature
     */
    public function encapsulate(
        string $classicalPublicKey,
        string $pqcPublicKey
    ): EncryptedData;
    
    /**
     * Decapsulate shared secret from hybrid KEM
     *
     * Decrypts both classical and PQC ciphertexts, then combines shared secrets.
     *
     * @param EncryptedData $encapsulated Encapsulated key data
     * @param string $classicalPrivateKey Classical algorithm private key
     * @param string $pqcPrivateKey Post-quantum algorithm private key
     * @return string Shared secret (base64-encoded)
     * @throws \Nexus\Crypto\Exceptions\FeatureNotImplementedException Phase 2 feature
     */
    public function decapsulate(
        EncryptedData $encapsulated,
        string $classicalPrivateKey,
        string $pqcPrivateKey
    ): string;
}
