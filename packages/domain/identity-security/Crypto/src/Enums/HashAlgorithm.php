<?php

declare(strict_types=1);

namespace Nexus\Crypto\Enums;

/**
 * Hash Algorithm Enum
 *
 * Supported hashing algorithms with security metadata.
 * Used for data integrity verification and checksums.
 */
enum HashAlgorithm: string
{
    case SHA256 = 'sha256';
    case SHA384 = 'sha384';
    case SHA512 = 'sha512';
    case BLAKE2B = 'blake2b';
    
    /**
     * Check if algorithm is quantum-resistant
     *
     * Note: All cryptographic hash functions are currently considered
     * quantum-resistant due to Grover's algorithm providing only
     * quadratic speedup (requiring doubled security level).
     */
    public function isQuantumResistant(): bool
    {
        return match ($this) {
            self::SHA256 => true,  // 256-bit → 128-bit quantum security (adequate)
            self::SHA384 => true,  // 384-bit → 192-bit quantum security
            self::SHA512 => true,  // 512-bit → 256-bit quantum security
            self::BLAKE2B => true, // 512-bit → 256-bit quantum security
        };
    }
    
    /**
     * Get security level in bits (classical)
     *
     * For quantum security, divide by 2 due to Grover's algorithm.
     */
    public function getSecurityLevel(): int
    {
        return match ($this) {
            self::SHA256 => 256,
            self::SHA384 => 384,
            self::SHA512 => 512,
            self::BLAKE2B => 512,
        };
    }
    
    /**
     * Get native PHP hash algorithm name
     *
     * Used with hash() function or Sodium equivalent.
     */
    public function getNativeAlgorithm(): string
    {
        return match ($this) {
            self::SHA256 => 'sha256',
            self::SHA384 => 'sha384',
            self::SHA512 => 'sha512',
            self::BLAKE2B => 'blake2b512', // Sodium uses blake2b512
        };
    }
    
    /**
     * Get output length in bytes
     */
    public function getOutputLength(): int
    {
        return match ($this) {
            self::SHA256 => 32,
            self::SHA384 => 48,
            self::SHA512 => 64,
            self::BLAKE2B => 64,
        };
    }
}
