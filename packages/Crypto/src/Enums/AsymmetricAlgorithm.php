<?php

declare(strict_types=1);

namespace Nexus\Crypto\Enums;

/**
 * Asymmetric Algorithm Enum
 *
 * Supported asymmetric cryptography algorithms for digital signatures
 * and key encapsulation, with post-quantum algorithm placeholders.
 */
enum AsymmetricAlgorithm: string
{
    // Phase 1: Classical Algorithms (Currently Implemented)
    case HMACSHA256 = 'hmac-sha256';
    case ED25519 = 'ed25519';
    case RSA2048 = 'rsa-2048';
    case RSA4096 = 'rsa-4096';
    case ECDSAP256 = 'ecdsa-p256';
    
    // Phase 2: Post-Quantum Algorithms (Stubs - FeatureNotImplementedException)
    case DILITHIUM3 = 'dilithium3';   // NIST ML-DSA Level 3
    case KYBER768 = 'kyber768';       // NIST ML-KEM Level 3
    
    /**
     * Check if algorithm is quantum-resistant
     *
     * Classical asymmetric algorithms (RSA, ECDSA, Ed25519) are vulnerable
     * to Shor's algorithm on quantum computers. Only post-quantum algorithms
     * are resistant.
     */
    public function isQuantumResistant(): bool
    {
        return match ($this) {
            // Classical algorithms - vulnerable to quantum attacks
            self::HMACSHA256 => false,     // Symmetric primitive (not PQC)
            self::ED25519 => false,        // Elliptic curve (Shor's algorithm)
            self::RSA2048 => false,        // RSA (Shor's algorithm)
            self::RSA4096 => false,        // RSA (Shor's algorithm)
            self::ECDSAP256 => false,      // Elliptic curve (Shor's algorithm)
            
            // Post-quantum algorithms - resistant
            self::DILITHIUM3 => true,      // Lattice-based signature
            self::KYBER768 => true,        // Lattice-based KEM
        };
    }
    
    /**
     * Get security level in bits (classical)
     *
     * Note: Quantum computers reduce effective security to 0 for non-PQC algorithms.
     */
    public function getSecurityLevel(): int
    {
        return match ($this) {
            self::HMACSHA256 => 256,
            self::ED25519 => 128,      // ~128-bit classical security
            self::RSA2048 => 112,      // ~112-bit classical security
            self::RSA4096 => 128,      // ~128-bit classical security
            self::ECDSAP256 => 128,    // ~128-bit classical security
            self::DILITHIUM3 => 192,   // NIST Level 3 (192-bit quantum security)
            self::KYBER768 => 192,     // NIST Level 3 (192-bit quantum security)
        };
    }
    
    /**
     * Get algorithm type (signature vs key encapsulation)
     */
    public function getType(): string
    {
        return match ($this) {
            self::HMACSHA256 => 'mac',
            self::ED25519 => 'signature',
            self::RSA2048 => 'signature',
            self::RSA4096 => 'signature',
            self::ECDSAP256 => 'signature',
            self::DILITHIUM3 => 'signature',
            self::KYBER768 => 'kem',  // Key Encapsulation Mechanism
        };
    }
    
    /**
     * Check if algorithm is currently implemented (Phase 1)
     *
     * Post-quantum algorithms will throw FeatureNotImplementedException until Phase 2.
     */
    public function isImplemented(): bool
    {
        return match ($this) {
            self::HMACSHA256,
            self::ED25519,
            self::RSA2048,
            self::RSA4096,
            self::ECDSAP256 => true,
            
            self::DILITHIUM3,
            self::KYBER768 => false,  // Phase 2 implementation
        };
    }
    
    /**
     * Get public key length in bytes (approximate)
     */
    public function getPublicKeyLength(): int
    {
        return match ($this) {
            self::HMACSHA256 => 0,      // Symmetric - no public key
            self::ED25519 => 32,
            self::RSA2048 => 294,       // DER-encoded
            self::RSA4096 => 550,       // DER-encoded
            self::ECDSAP256 => 65,      // Uncompressed point
            self::DILITHIUM3 => 1952,   // Significantly larger (PQC characteristic)
            self::KYBER768 => 1184,
        };
    }
    
    /**
     * Get signature length in bytes (approximate)
     */
    public function getSignatureLength(): int
    {
        return match ($this) {
            self::HMACSHA256 => 32,
            self::ED25519 => 64,
            self::RSA2048 => 256,
            self::RSA4096 => 512,
            self::ECDSAP256 => 64,      // r + s values
            self::DILITHIUM3 => 3293,   // Significantly larger (PQC characteristic)
            self::KYBER768 => 0,        // KEM, not signature
        };
    }
}
