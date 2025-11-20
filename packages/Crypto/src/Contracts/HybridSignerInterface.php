<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\ValueObjects\SignedData;

/**
 * Hybrid Signer Interface (Phase 2 - PQC)
 *
 * Provides hybrid digital signatures combining classical and post-quantum algorithms.
 * Performs dual signing (e.g., ECDSA + Dilithium) for quantum-resistant signatures
 * while maintaining backward compatibility.
 *
 * STATUS: Not yet implemented (Phase 2 feature).
 * Will throw FeatureNotImplementedException until Q3 2026.
 */
interface HybridSignerInterface
{
    /**
     * Sign data with both classical and PQC algorithms
     *
     * Generates two signatures:
     * 1. Classical (e.g., ECDSA-P256) for current security
     * 2. Post-quantum (e.g., Dilithium3) for future security
     *
     * Signatures are concatenated in SignedData::signature field.
     *
     * @param string $data Data to sign
     * @param string $classicalPrivateKey Classical algorithm private key
     * @param string $pqcPrivateKey Post-quantum algorithm private key
     * @return SignedData Hybrid signed data (concatenated signatures)
     * @throws \Nexus\Crypto\Exceptions\FeatureNotImplementedException Phase 2 feature
     */
    public function signHybrid(
        string $data,
        string $classicalPrivateKey,
        string $pqcPrivateKey
    ): SignedData;
    
    /**
     * Verify hybrid signature
     *
     * Verifies both classical and PQC signatures.
     * Both must be valid for verification to succeed.
     *
     * @param SignedData $signed Hybrid signed data
     * @param string $classicalPublicKey Classical algorithm public key
     * @param string $pqcPublicKey Post-quantum algorithm public key
     * @return bool True if both signatures are valid
     * @throws \Nexus\Crypto\Exceptions\FeatureNotImplementedException Phase 2 feature
     */
    public function verifyHybrid(
        SignedData $signed,
        string $classicalPublicKey,
        string $pqcPublicKey
    ): bool;
}
